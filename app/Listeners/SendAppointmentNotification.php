<?php

namespace App\Listeners;

use App\Events\AppointmentSaved;
use App\Mail\AppointmentStatusUpdated;
use App\Models\Appointment;
use Illuminate\Support\Facades\Mail;

class SendAppointmentNotification
{
    /**
     * Email the resident when a booking is accepted (approved) or not accepted (cancelled).
     * Sends for every plan tier (Basic, Standard, Premium); the plan's has_email_notifications column remains for display/reporting.
     * Same pattern as tenant-application approval/rejection: synchronous send with try/catch so failures are logged, not lost to a queue.
     */
    public function handle(AppointmentSaved $event): void
    {
        $appointment = $event->appointment->load(['resident', 'tenant.plan', 'tenant.domains']);

        if (! in_array($appointment->status, [Appointment::STATUS_APPROVED, Appointment::STATUS_CANCELLED], true)) {
            return;
        }

        $resident = $appointment->resident;
        if (! $resident?->email) {
            return;
        }

        try {
            Mail::mailer(config('mail.default'))->to($resident->email)->send(new AppointmentStatusUpdated($appointment));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
