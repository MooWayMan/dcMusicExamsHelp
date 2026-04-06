<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormSubmission extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public ?string $senderSubject,
        public string $senderMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contact Form: ' . ($this->senderSubject ?? 'New enquiry from ' . $this->senderName),
            replyTo: [$this->senderEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.contact-form',
        );
    }
}
