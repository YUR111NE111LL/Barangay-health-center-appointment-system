<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTenantBarangayAdministrator
{
    /**
     * Health Center Admin or a tenant custom role with equivalent barangay administration rights.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'hasTenantBarangayAdministrationAccess')) {
            abort(403, 'You do not have permission to access this area.');
        }

        if (! $user->hasTenantBarangayAdministrationAccess()) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
