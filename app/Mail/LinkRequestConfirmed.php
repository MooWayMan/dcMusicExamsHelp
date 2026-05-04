<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Receipt sent to the user confirming their link-request has been
 * received. We'll match the supplied Trinity email to the right
 * exam_contacts row manually, then email them when their dashboard is
 * ready.
 */
class LinkRequestConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $alternativeEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "We've got your account link request",
            from: 'musicexams@musicexams.help',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.link-confirmed',
            with: [
                'firstName' => $this->firstName(),
                'alternativeEmail' => $this->alternativeEmail,
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
