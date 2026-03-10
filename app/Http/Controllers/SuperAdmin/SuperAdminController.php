<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        $tenantCount = Tenant::count();
        $userCount = User::count();
        $appointmentCount = Appointment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->count();
        $appointmentsThisMonth = Appointment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->whereMonth('scheduled_date', now()->month)
            ->whereYear('scheduled_date', now()->year)
            ->count();

        return view('superadmin.dashboard', [
            'tenantCount' => $tenantCount,
            'userCount' => $userCount,
            'appointmentCount' => $appointmentCount,
            'appointmentsThisMonth' => $appointmentsThisMonth,
        ]);
    }

    /**
     * List all Super Admin accounts (users registered as Super Admin, tenant_id null).
     */
    public function accounts(): View
    {
        $users = User::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->orderBy('name')
            ->get();

        return view('superadmin.accounts.index', compact('users'));
    }
}
