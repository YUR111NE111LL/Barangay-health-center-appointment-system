<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByDomainForAuth
{
    /**
     * When on a tenant domain, initialize tenancy so login knows the current tenant.
     * When on central (localhost etc.), skip so central login is shown.
     * When domain is not registered, redirect to central with a friendly message instead of 500.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost']);

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        try {
            return app(InitializeTenancyByDomain::class)->handle($request, $next);
        } catch (TenantCouldNotBeIdentifiedException $e) {
            $centralHost = $centralDomains[0] ?? 'localhost';
            $centralUrl = $request->getScheme() . '://' . $centralHost;
            if (! in_array($request->getPort(), [80, 443], true) && $request->getPort()) {
                $centralUrl .= ':' . $request->getPort();
            }
            return redirect()->away($centralUrl . '/login')
                ->withErrors(['email' => 'This address (' . $host . ') is not registered for any barangay. Use your barangay\'s correct URL or log in from the central site.']);
        }
    }
}
