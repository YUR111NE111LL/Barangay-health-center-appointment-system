<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment
    ) {}

    public function envelope(): Envelope
    {
        $status = $this->appointment->status;
        $subject = match ($status) {
            'approved' => 'Appointment Approved',
            'cancelled' => 'Appointment Cancelled',
            'no_show' => 'Appointment Status Update',
            default => 'Appointment Status Update',
        };

        return new Envelope(
            subject: $subject . ' – ' . config('bhcas.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-status-updated',
        );
    }
}
