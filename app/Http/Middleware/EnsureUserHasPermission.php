<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Allow only if the user has the given permission (RBAC / tenant_role_permissions).
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! auth()->user()->can($permission)) {
            throw new AuthorizationException(__('Your health center admin has disabled this for your account. Permissions are set per tenant plan. Contact your health center if you need this access.'));
        }

        return $next($request);
    }
}
