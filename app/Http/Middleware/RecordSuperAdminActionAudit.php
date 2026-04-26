<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SuperAdminAuditRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RecordSuperAdminActionAudit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();
        if ($user instanceof User && $user->isSuperAdmin()) {
            SuperAdminAuditRecorder::recordAction($user, $request, $response);
        }

        return $response;
    }
}
