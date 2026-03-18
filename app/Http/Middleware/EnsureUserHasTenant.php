<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTenant
{
    /**
     * Ensure the user belongs to a tenant (not Super Admin). On tenant domains, ensure user belongs to that tenant.
     * Also check if tenant is active and not past grace period.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! auth()->user()->hasTenant()) {
            abort(403, 'This area is for health center staff. Super Admin should use the Super Admin dashboard.');
        }

        $tenant = auth()->user()->tenant;

        // When on a tenant domain, user must belong to that tenant
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost']);
        if (! in_array($host, $centralDomains, true)) {
            $domainTenant = Domain::where('domain', $host)->first()?->tenant;
            if ($domainTenant && (int) $domainTenant->id !== (int) $tenant->id) {
                return redirect()->route('login')->withErrors(['email' => 'This account belongs to another barangay. Please use your barangay\'s website to log in.']);
            }
        }
        
        // Check if tenant is deactivated
        if (!$tenant->is_active) {
            abort(403, 'Your system has been deactivated. Please contact the Super Admin to renew your subscription.');
        }

        // Check if tenant is past grace period (should be deactivated, but double-check)
        if ($tenant->isPastGracePeriod()) {
            // Auto-deactivate if somehow still active
            $tenant->update(['is_active' => false]);
            abort(403, 'Your subscription has expired and the grace period has ended. Please contact the Super Admin to renew your subscription.');
        }

        return $next($request);
    }
}
