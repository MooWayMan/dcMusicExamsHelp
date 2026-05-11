<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Sends the Trinity Exam Checklist PDF as an attachment to a new
 * lead-magnet subscriber.
 *
 * URL resolution (see pdfUrl() below):
 *
 *  1. Preferred — if AWS credentials are configured, generate a short-lived
 *     presigned URL via the S3 disk. The S3 object itself should be PRIVATE;
 *     the presigned URL is the only access path and expires after 15 minutes.
 *     This is the production setup post the lead-magnet PDF gating work.
 *
 *  2. Fallback — if AWS isn't configured (e.g. local dev), fall back to the
 *     public URL via `LEAD_MAGNET_PDF_URL` env var or the hard-coded default.
 *     Lets local development keep working without committing AWS keys to a
 *     non-secret place.
 *
 * See docs/dev-rules.md "Lead magnet PDF gating" rule for the rationale.
 */
class LeadMagnetDelivery extends Mailable
{
    use Queueable, SerializesModels;

    // Public URL — only used as the fallback when AWS credentials aren't
    // available. Once the S3 object is made private (which is the whole
    // point of the gating work) this URL will return 403 and the fallback
    // path becomes a "log warning, send email without attachment" branch.
    public const DEFAULT_PDF_URL = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Trinity+Exam+Checklist.pdf';

    public function __construct(
        public string $subscriberName,
    ) {}

    private function pdfUrl(): string
    {
        // Preferred: presigned S3 URL. The IAM user's access key signs a URL
        // that's valid for 15 minutes; the bucket object itself should be
        // private so the signed URL is the only path in.
        if (config('filesystems.disks.s3.key') && config('filesystems.disks.s3.bucket')) {
            $path = (string) (config('lead_magnet.pdf_path') ?: env('LEAD_MAGNET_PDF_PATH', 'musicexamshelp/Trinity Exam Checklist.pdf'));
            try {
                return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));
            } catch (\Throwable $e) {
                Log::warning('LeadMagnetDelivery: presigned URL generation failed, falling back to public URL: '.$e->getMessage());
            }
        }

        // Fallback — public URL. Works while the S3 object is still public
        // (pre-gating) or when AWS credentials simply aren't available
        // locally. Once the object is made private this branch will fetch
        // and get a 403, the attachment will be skipped, and the email
        // sends without the PDF (with a warning logged).
        return (string) (config('lead_magnet.pdf_url') ?: env('LEAD_MAGNET_PDF_URL', self::DEFAULT_PDF_URL));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Trinity Exam Checklist is here',
            from: 'musicexams@musicexams.help',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.lead-magnet-delivery',
            with: [
                'firstName' => $this->firstName(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        // Fetch the PDF over HTTP from its public URL. We use the Http
        // facade so failures (404, network) are caught cleanly — if the
        // PDF is unreachable the email still sends without it rather
        // than throwing and breaking the subscribe flow.
        try {
            $response = Http::timeout(8)->get($this->pdfUrl());

            if (! $response->successful()) {
                Log::warning('LeadMagnetDelivery: PDF fetch failed', [
                    'status' => $response->status(),
                    'url' => $this->pdfUrl(),
                ]);
                return [];
            }

            $contents = $response->body();
        } catch (\Throwable $e) {
            Log::warning('LeadMagnetDelivery: PDF fetch exception: '.$e->getMessage());
            return [];
        }

        return [
            Attachment::fromData(fn () => $contents, 'trinity-exam-checklist.pdf')
                ->withMime('application/pdf'),
        ];
    }

    private function firstName(): string
    {
        // Just use the first space-separated word — works for the
        // overwhelming majority of real-world inputs ("Paul Sheridan" →
        // "Paul"). Edge cases like just "Mr" or "Mr Sheridan" produce
        // slightly weird greetings, but the alternatives (title-stripping)
        // produce equally weird results in different cases. Keeping it
        // simple and predictable.
        $name = trim($this->subscriberName);
        if ($name === '') {
            return 'there';
        }

        return explode(' ', $name)[0];
    }
}
