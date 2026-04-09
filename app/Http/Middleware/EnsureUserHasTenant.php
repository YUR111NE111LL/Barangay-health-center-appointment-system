<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTenant
{
    /**
     * Ensure the user belongs to a tenant (not Super Admin). On tenant domains, ensure user belongs to that tenant.
     * Also check if tenant is active and not past grace period.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->to('/login');
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user?->hasTenant()) {
            $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost']);
            $centralHost = $centralDomains[0] ?? 'localhost';
            $centralUrl = $request->getScheme().'://'.$centralHost;
            if (! in_array((int) $request->getPort(), [80, 443], true) && $request->getPort()) {
                $centralUrl .= ':'.$request->getPort();
            }

            return redirect()->away($centralUrl.'/super-admin')
                ->withErrors([
                    'email' => 'Super Admin accounts should use the central dashboard.',
                ]);
        }

        // Keep actual Resident role users in resident area, but do not force custom "resident-like"
        // users away from backend pages because that can switch portal cookies and appear as logout.
        if ($user->role === \App\Models\User::ROLE_RESIDENT && ! $request->routeIs('resident.*') && ! $request->routeIs('backend.support.*')) {
            return redirect()->route('resident.dashboard');
        }

        $tenant = $user->tenant;

        // When on a tenant domain, user must belong to that tenant
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost']);
        if (! in_array($host, $centralDomains, true)) {
            $domainTenant = Domain::where('domain', $host)->first()?->tenant;
            if ($domainTenant && (int) $domainTenant->id !== (int) $tenant->id) {
                return redirect()->to('/login')->withErrors(['email' => 'This account belongs to another barangay. Please use your barangay\'s website to log in.']);
            }
        }

        // Check if tenant is deactivated
        if (! $tenant->is_active) {
            abort(403, 'Your system has been deactivated. Please contact the Super Admin to renew your subscription.');
        }

        // Check if tenant is past grace period (should be deactivated, but double-check)
        if ($tenant->isPastGracePeriod()) {
            // Auto-deactivate if somehow still active
            $tenant->update(['is_active' => false]);
            abort(403, 'Your subscription has expired and the grace period has ended. Please contact the Super Admin to renew your subscription.');
        }

        // Ensure tenant tenancy is initialized for the rest of this request.
        // Without this, tenant routes can accidentally continue using the central DB.
        $initialized = false;
        if (! tenant() || (int) tenant()->id !== (int) $tenant->id) {
            try {
                tenancy()->initialize($tenant);
                $initialized = true;
            } catch (TenantDatabaseDoesNotExistException $e) {
                // Tenant record exists but its database wasn't provisioned yet.
                // Redirect to central to avoid a raw 500 for end users.
                return redirect()->to('/login')->withErrors([
                    'email' => 'This barangay is not ready yet (tenant database not found). Please contact the Super Admin.',
                ]);
            }
        }

        try {
            return $next($request);
        } finally {
            if ($initialized) {
                tenancy()->end();
            }
        }
    }
}
