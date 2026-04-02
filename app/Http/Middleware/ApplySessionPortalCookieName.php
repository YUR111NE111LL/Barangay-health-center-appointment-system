<?php

namespace App\Http\Middleware;

use App\Support\SessionPortal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Must run before StartSession so the per-portal session cookie name matches the logged-in context.
 * Implemented as middleware (not only in AppServiceProvider::boot) so POST bodies (e.g. logout session_portal) are available.
 */
class ApplySessionPortalCookieName
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        SessionPortal::applySessionCookieName($request);

        return $next($request);
    }
}
