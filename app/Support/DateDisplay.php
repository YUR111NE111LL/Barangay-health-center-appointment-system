<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class DateDisplay
{
    /**
     * Format an app/DB datetime for display in the configured UI timezone (e.g. Asia/Manila).
     */
    public static function format(?CarbonInterface $date, string $format = 'M j, Y g:i A'): string
    {
        if ($date === null) {
            return '—';
        }

        $tz = (string) config('bhcas.display_timezone', 'Asia/Manila');

        return $date->timezone($tz)->format($format);
    }
}
