<?php

namespace App\Mail;

use App\Models\ExamEntry;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notification to the admin (Paul) when a teacher reports a correction
 * on one of their candidate rows. Pairs with CorrectionRequestConfirmed
 * which goes to the user.
 */
class CorrectionRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public User $user,
        public ExamEntry $entry,
    ) {}

    public function envelope(): Envelope
    {
        $candidate = $this->entry->candidate_name ?? 'unknown candidate';

        return new Envelope(
            subject: "Correction request: {$candidate}",
            replyTo: [$this->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.correction-submitted',
        );
    }
}
