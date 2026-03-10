<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Models\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAppointmentReminders extends Command
{
    protected $signature = 'app:send-appointment-reminders';

    protected $description = "Queue email reminders for tomorrow's approved appointments (all tenants).";

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $appointments = Appointment::withoutGlobalScope(TenantScope::class)
            ->with(['resident', 'service'])
            ->whereDate('scheduled_date', $tomorrow)
            ->where('status', Appointment::STATUS_APPROVED)
            ->get();

        $count = 0;
        foreach ($appointments as $appointment) {
            $resident = $appointment->resident;
            if ($resident && $resident->email) {
                Mail::to($resident->email)->queue(new AppointmentReminder($appointment));
                $count++;
            }
        }

        $this->info("Queued {$count} appointment reminder(s) for {$tomorrow}.");

        return self::SUCCESS;
    }
}
