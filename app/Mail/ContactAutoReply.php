<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactAutoReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public ?string $senderSubject,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thanks for getting in touch — musicExams.help',
            from: 'musicexams@musicexams.help',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.contact-auto-reply',
        );
    }
}
