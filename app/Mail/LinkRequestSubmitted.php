<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notification to the admin (Paul) when a logged-in user submits the
 * linkage form because their registered email doesn't match anything
 * in exam_entries. Pairs with LinkRequestConfirmed which goes to the
 * user.
 */
class LinkRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public User $user,
        public string $alternativeEmail,
        public ?string $note = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Account link request: {$this->user->email}",
            replyTo: [$this->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.link-submitted',
        );
    }
}
