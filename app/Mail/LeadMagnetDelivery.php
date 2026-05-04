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

/**
 * Sends the Trinity Exam Checklist PDF as an attachment to a new
 * lead-magnet subscriber. The PDF lives at a public S3 URL — Paul keeps
 * his static assets in moowaymusicbucket — and the Mailable fetches it
 * over HTTP, so no AWS credentials are required to attach it. The URL
 * is configurable via env `LEAD_MAGNET_PDF_URL` for future flexibility.
 */
class LeadMagnetDelivery extends Mailable
{
    use Queueable, SerializesModels;

    // Default — Paul's public S3 URL for the Trinity Exam Checklist PDF.
    // Override via .env LEAD_MAGNET_PDF_URL if the asset moves.
    public const DEFAULT_PDF_URL = 'https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/Trinity+Exam+Checklist.pdf';

    public function __construct(
        public string $subscriberName,
    ) {}

    private function pdfUrl(): string
    {
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
