<?php

use App\Models\AuditLog;
use App\Models\Event;
use App\Support\TenantAuditLogDisplay;

test('community event label distinguishes calendar events from generic Event', function () {
    expect(TenantAuditLogDisplay::auditableLabel(Event::class))->toBe('Community event');
});

test('event context line uses title from new or old values', function () {
    $created = new AuditLog([
        'event' => 'created',
        'auditable_type' => Event::class,
        'auditable_id' => 1,
        'new_values' => ['title' => 'Barangay vaccination day'],
        'old_values' => null,
    ]);
    expect(TenantAuditLogDisplay::auditableContextLine($created))->toBe('Barangay vaccination day');

    $deleted = new AuditLog([
        'event' => 'deleted',
        'auditable_type' => Event::class,
        'auditable_id' => 2,
        'new_values' => null,
        'old_values' => ['title' => 'Removed listing'],
    ]);
    expect(TenantAuditLogDisplay::auditableContextLine($deleted))->toBe('Removed listing');
});

test('login and logout rows have no context line', function () {
    $log = new AuditLog([
        'event' => 'login',
        'auditable_type' => \App\Models\User::class,
        'auditable_id' => 1,
        'new_values' => ['remember' => false],
        'old_values' => null,
    ]);
    expect(TenantAuditLogDisplay::auditableContextLine($log))->toBeNull();
});
