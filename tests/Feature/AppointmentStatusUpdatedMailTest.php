<?php

use App\Mail\AppointmentStatusUpdated;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;

it('builds appointment approved mailable with envelope and view', function (): void {
    $resident = new User(['name' => 'Jane Resident', 'email' => 'jane@example.com']);
    $service = new Service(['name' => 'General consultation']);
    $appointment = new Appointment([
        'status' => Appointment::STATUS_APPROVED,
        'scheduled_date' => now()->startOfDay(),
        'scheduled_time' => '09:00:00',
    ]);
    $appointment->setRelation('resident', $resident);
    $appointment->setRelation('service', $service);

    $mailable = new AppointmentStatusUpdated($appointment);
    $envelope = $mailable->envelope();
    $content = $mailable->content();

    expect($content->view)->toBe('emails.appointment-status-updated');
    expect($envelope->subject)->toContain('approved');
    expect($mailable->appointment->status)->toBe(Appointment::STATUS_APPROVED);
});

it('builds appointment cancelled mailable subject for not approved', function (): void {
    $resident = new User(['name' => 'Jane Resident', 'email' => 'jane@example.com']);
    $service = new Service(['name' => 'General consultation']);
    $appointment = new Appointment([
        'status' => Appointment::STATUS_CANCELLED,
        'scheduled_date' => now()->startOfDay(),
        'scheduled_time' => '14:30:00',
    ]);
    $appointment->setRelation('resident', $resident);
    $appointment->setRelation('service', $service);

    $mailable = new AppointmentStatusUpdated($appointment);
    $envelope = $mailable->envelope();

    expect($envelope->subject)->toContain('not approved');
});
