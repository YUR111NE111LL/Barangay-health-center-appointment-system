<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
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
            Appointment::STATUS_APPROVED => __('Your appointment was approved – :app', ['app' => config('bhcas.name')]),
            Appointment::STATUS_CANCELLED => __('Your appointment request was not approved – :app', ['app' => config('bhcas.name')]),
            'no_show' => __('Appointment status update – :app', ['app' => config('bhcas.name')]),
            default => __('Appointment status update – :app', ['app' => config('bhcas.name')]),
        };

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-status-updated',
        );
    }
}
