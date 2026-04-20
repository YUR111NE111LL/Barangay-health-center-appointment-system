<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TenantAnnouncementEmail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $posterName,
        public string $recipientName,
        public string $title,
        public string $excerpt,
        public string $viewUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: __('New health announcement – :poster', ['poster' => $this->posterName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-announcement',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
