<?php

namespace App\Mail;

use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSiteReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $organizationName,
        public string $domain,
        public ?Plan $plan,
        public string $staffLoginUrl,
        public string $residentLoginUrl,
        public ?string $subjectOverride = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: $this->subjectOverride ?? __('Your barangay site is ready – :app', ['app' => config('bhcas.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-site-ready',
        );
    }
}
