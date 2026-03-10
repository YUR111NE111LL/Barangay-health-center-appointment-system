<?php

namespace App\Listeners;

use App\Events\AppointmentSaved;
use App\Mail\AppointmentStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendAppointmentNotification implements ShouldQueue
{
    /**
     * Send approved/denied (or any status) update to the resident.
     * Residents who registered with Google (or email) receive this at their email when the tenant's plan allows.
     */
    public function handle(AppointmentSaved $event): void
    {
        $appointment = $event->appointment->load(['resident', 'tenant.plan']);
        $resident = $appointment->resident;
        if (! $resident?->email) {
            return;
        }

        $tenant = $appointment->tenant;
        if ($tenant && $tenant->plan && ! $tenant->plan->has_email_notifications) {
            return;
        }

        Mail::to($resident->email)->send(new AppointmentStatusUpdated($appointment));
    }
}
