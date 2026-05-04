<?php

namespace App\Mail;

use App\Models\ExamEntry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Receipt sent to the user (teacher / parent / self) confirming we've
 * logged their correction. Reassures them that we'll handle the fix on
 * musicExams.help and with Trinity — they don't need to contact Trinity
 * themselves (per CLAUDE.md booking rules).
 */
class CorrectionRequestConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ExamEntry $entry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "We've got your correction request",
            from: 'musicexams@musicexams.help',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.correction-confirmed',
            with: [
                'firstName' => $this->firstName(),
                'candidateName' => $this->entry->candidate_name ?? 'your candidate',
            ],
        );
    }

    private function firstName(): string
    {
        $name = trim((string) $this->user->name);
        if ($name === '') {
            return 'there';
        }

        return explode(' ', $name)[0];
    }
}
