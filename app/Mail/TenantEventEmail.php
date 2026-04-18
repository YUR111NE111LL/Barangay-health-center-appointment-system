<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TenantEventEmail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $barangayName,
        public string $recipientName,
        public string $title,
        public string $excerpt,
        public string $whenLine,
        public ?string $location,
        public string $viewUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: __('New community health event – :barangay', ['barangay' => $this->barangayName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-event',
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
