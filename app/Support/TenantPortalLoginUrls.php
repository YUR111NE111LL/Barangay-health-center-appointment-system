<?php

namespace App\Support;

/**
 * Builds staff/resident login URLs for a tenant host using the current request scheme and port.
 */
final class TenantPortalLoginUrls
{
    /**
     * @return array{staff: string, resident: string}
     */
    public static function forDomain(string $domain): array
    {
        $scheme = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = ($port && ! in_array((int) $port, [80, 443], true)) ? ':'.$port : '';
        $base = $scheme.'://'.$domain.$portSuffix;

        return [
            'staff' => $base.'/login?for=tenant',
            'resident' => $base.'/login?for=resident',
        ];
    }
}
