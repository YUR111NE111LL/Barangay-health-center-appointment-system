<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Medicine;
use App\Models\Service;
use App\Models\User;

/**
 * Human-readable labels for tenant audit log rows (especially distinguishing
 * {@see Event} listings from generic "Event" wording).
 */
final class TenantAuditLogDisplay
{
    public static function auditableLabel(string $auditableType): string
    {
        return match ($auditableType) {
            Event::class => 'Community event',
            Announcement::class => 'Announcement',
            Service::class => 'Service',
            Appointment::class => 'Appointment',
            Medicine::class => 'Medicine',
            User::class => 'User account',
            default => class_basename($auditableType),
        };
    }

    public static function auditableContextLine(AuditLog $log): ?string
    {
        if (in_array($log->event, ['login', 'logout'], true)) {
            return null;
        }

        $old = $log->old_values;
        $new = $log->new_values;

        return match ($log->auditable_type) {
            Event::class => self::firstNonEmptyString($new, $old, ['title']),
            Announcement::class => self::firstNonEmptyString($new, $old, ['title']),
            Service::class => self::firstNonEmptyString($new, $old, ['name']),
            User::class => self::firstNonEmptyString($new, $old, ['name', 'email']),
            Appointment::class => self::appointmentContextLine($old, $new),
            Medicine::class => self::medicineAcquireContextLine($new),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $oldValues
     * @param  list<string>  $keys
     */
    private static function firstNonEmptyString(?array $newValues, ?array $oldValues, array $keys): ?string
    {
        foreach ([$newValues, $oldValues] as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($keys as $key) {
                $v = $row[$key] ?? null;
                if (is_string($v) && $v !== '') {
                    return $v;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $newValues
     */
    private static function medicineAcquireContextLine(?array $newValues): ?string
    {
        if (! is_array($newValues)) {
            return null;
        }

        $name = isset($newValues['name']) && is_string($newValues['name']) ? $newValues['name'] : '';
        $took = $newValues['quantity_acquired'] ?? null;
        $left = $newValues['quantity_remaining'] ?? null;
        $lineTotal = $newValues['line_total'] ?? null;
        $isFree = $newValues['is_free'] ?? null;

        $parts = [];
        if ($name !== '') {
            $parts[] = $name;
        }
        if (is_numeric($took)) {
            $parts[] = (int) $took.' unit(s) acquired';
        }
        if (is_numeric($left)) {
            $parts[] = (int) $left.' remaining in stock';
        }
        if ($isFree === false && is_numeric($lineTotal)) {
            $sym = config('bhcas.currency_symbol', '₱');
            $parts[] = $sym.number_format((float) $lineTotal, 2).' line total';
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private static function appointmentContextLine(?array $oldValues, ?array $newValues): ?string
    {
        $date = self::firstScalarString($newValues, $oldValues, ['scheduled_date']);
        $time = self::firstScalarString($newValues, $oldValues, ['scheduled_time']);
        if ($date === null && $time === null) {
            return null;
        }

        $parts = array_filter([$date, $time], fn (?string $s): bool => $s !== null && $s !== '');

        return $parts === [] ? null : 'Scheduled '.implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>|null  $a
     * @param  array<string, mixed>|null  $b
     * @param  list<string>  $keys
     */
    private static function firstScalarString(?array $a, ?array $b, array $keys): ?string
    {
        foreach ([$a, $b] as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($keys as $key) {
                $v = $row[$key] ?? null;
                if ($v === null) {
                    continue;
                }
                if (is_string($v) && $v !== '') {
                    return $v;
                }
                if (is_scalar($v)) {
                    return (string) $v;
                }
            }
        }

        return null;
    }
}
