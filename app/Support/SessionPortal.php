<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Single source of truth for session cookie naming (host + portal) so GET/POST and
 * tab behaviour stay aligned. Portal keys match AppServiceProvider behaviour before extraction.
 */
class SessionPortal
{
    /**
     * Logical portal for this request (used in session cookie name and client tab sync).
     */
    public static function portalKey(Request $request): string
    {
        // Set by Google/email SSO redirect so the session cookie matches /backend (staff) or /resident (resident)
        // before Auth::login; otherwise this path is "public" and the next request to /backend uses a different cookie.
        $portalHint = (string) $request->query('portal', '');
        if ($portalHint === 'resident' || $portalHint === 'staff') {
            return $portalHint === 'resident' ? 'resident' : 'staff';
        }

        $path = '/'.ltrim((string) $request->path(), '/');
        $pathTrim = ltrim((string) $request->path(), '/');
        $for = (string) $request->query('for', $request->input('for', ''));
        $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost']);
        $isCentralHost = in_array($request->getHost(), $centralDomains, true);

        // /logout is not under /backend or /resident; without this we would use the "public" session cookie while
        // the user is logged in under staff/resident/superadmin — CSRF + auth fail and logout appears broken.
        if ($pathTrim === 'logout') {
            $fromForm = (string) $request->input('session_portal', '');
            if (in_array($fromForm, ['staff', 'resident', 'superadmin', 'public'], true)) {
                return $fromForm;
            }
            $referer = (string) $request->header('Referer', '');
            if (str_contains($referer, '/super-admin')) {
                return 'superadmin';
            }
            if (str_contains($referer, '/resident')) {
                return 'resident';
            }
            if (str_contains($referer, '/backend')) {
                return 'staff';
            }
            if (! $isCentralHost) {
                return 'staff';
            }

            return 'public';
        }

        // Central hosts only serve Super Admin login at /login; POST always sends for=super-admin.
        // GET usually has no ?for= query, which would pick the "public" portal and a different session — CSRF 419.
        if ($isCentralHost && $pathTrim === 'login') {
            return 'superadmin';
        }

        if ($for === '' && ! $isCentralHost && in_array($pathTrim, ['login', 'register', 'sign-up', 'forgot-password'], true)) {
            $for = 'resident';
        }
        if (str_starts_with($path, '/super-admin') || $for === 'super-admin') {
            return 'superadmin';
        }
        if (str_starts_with($path, '/backend') || $for === 'tenant') {
            return 'staff';
        }
        if (str_starts_with($path, '/resident') || $for === 'resident') {
            return 'resident';
        }

        return 'public';
    }

    /**
     * Apply per-host, per-portal session cookie name (isolates Super Admin / staff / resident sessions).
     */
    public static function applySessionCookieName(Request $request): void
    {
        $host = $request->getHost();
        $baseCookieName = (string) config('session.cookie', 'laravel-session');
        $hostCookieSuffix = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($host)) ?? 'app';
        $portal = self::portalKey($request);
        config(['session.cookie' => $baseCookieName.'_'.$hostCookieSuffix.'_'.$portal]);
    }
}
