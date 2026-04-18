<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanExpiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Tenant $tenant,
        public string $type // 'expiring_soon', 'expired_grace_period', 'deactivated'
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'expiring_soon' => '⚠️ Plan Expiring Soon – '.$this->tenant->name,
            'expired_grace_period' => '🚨 Plan Expired – Grace Period Active – '.$this->tenant->name,
            'deactivated' => '❌ System Deactivated – '.$this->tenant->name,
            default => 'Plan Status Update – '.$this->tenant->name,
        };

        return new Envelope(
            subject: $subject.' – '.config('bhcas.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-expiry-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
