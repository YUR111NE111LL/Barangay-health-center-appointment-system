<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Normalizes user-entered barangay/domain fields (same rules as Super Admin tenant creation).
 */
final class TenantDomainInput
{
    public static function normalizeDomain(string $input): string
    {
        $domain = trim($input);

        if ($domain === '') {
            return '';
        }

        $domain = preg_replace('#^https?://#i', '', $domain) ?: $domain;
        $domain = preg_replace('/[\/\?#].*$/', '', $domain) ?: $domain;
        $domain = rtrim($domain, '.');

        if (str_starts_with($domain, '[') && str_contains($domain, ']')) {
            $endBracketPos = strpos($domain, ']');
            $host = substr($domain, 1, $endBracketPos - 1);

            return Str::lower(trim($host));
        }

        if (substr_count($domain, ':') === 1) {
            $colonPos = strrpos($domain, ':');
            $host = substr($domain, 0, $colonPos);
            $port = substr($domain, $colonPos + 1);

            if (ctype_digit($port)) {
                $domain = $host;
            }
        }

        return Str::lower(trim($domain));
    }

    public static function deriveDomainFromBarangay(string $barangay): string
    {
        $raw = trim($barangay);
        if ($raw === '') {
            return '';
        }

        if (str_contains($raw, '.') || str_contains($raw, '://')) {
            return self::normalizeDomain($raw);
        }

        $slug = Str::slug($raw);
        if ($slug === '') {
            return '';
        }

        $root = (string) config('bhcas.tenant_domain_root', 'localhost');

        return self::normalizeDomain($slug.'.'.$root);
    }
}
