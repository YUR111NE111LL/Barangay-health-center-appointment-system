<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendingApprovalsController extends Controller
{
    /** List users pending Super Admin approval (Barangay Admin and Super Admin signups only). */
    public function index(): View
    {
        $pending = User::withoutGlobalScopes()
            ->whereIn('role', User::rolesApprovedBySuperAdmin())
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->with(['tenant.domains'])
            ->get();

        return view('superadmin.pending-approvals.index', compact('pending'));
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        if (! in_array($user->role, User::rolesApprovedBySuperAdmin(), true) || $user->is_approved) {
            return redirect()->route('super-admin.pending-approvals.index')
                ->with('error', 'That account is not pending approval.');
        }

        // Keep tenant-domain authentication isolated to tenant DB users.
        // When approving a barangay admin from central pending approvals,
        // ensure the corresponding account exists in the tenant database.
        if ($user->role === User::ROLE_HEALTH_CENTER_ADMIN && $user->tenant_id) {
            $tenant = Tenant::query()->find($user->tenant_id);
            if ($tenant) {
                $tenant->run(function () use ($user): void {
                    $tenantUser = User::query()
                        ->where('tenant_id', $user->tenant_id)
                        ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
                        ->first();

                    if (! $tenantUser) {
                        User::query()->create([
                            'tenant_id' => $user->tenant_id,
                            'role' => $user->role,
                            'name' => $user->name,
                            'purok_address' => $user->purok_address,
                            'profile_picture' => $user->profile_picture,
                            'email' => $user->email,
                            'password' => $user->password,
                            'google_id' => $user->google_id,
                            'is_approved' => true,
                        ]);
                    } else {
                        $tenantUser->update([
                            'role' => $user->role,
                            'name' => $user->name,
                            'purok_address' => $user->purok_address,
                            'profile_picture' => $user->profile_picture,
                            'password' => $user->password,
                            'google_id' => $user->google_id,
                            'is_approved' => true,
                        ]);
                    }
                });
            }
        }

        $user->update(['is_approved' => true]);

        return redirect()->route('super-admin.pending-approvals.index')
            ->with('success', "{$user->name} ({$user->role}) has been approved. They can now log in.");
    }

    public function deny(Request $request, User $user): RedirectResponse
    {
        if (! in_array($user->role, User::rolesApprovedBySuperAdmin(), true) || $user->is_approved) {
            return redirect()->route('super-admin.pending-approvals.index')
                ->with('error', 'That account is not pending approval.');
        }
        $wasSuperAdminSignup = $user->role === User::ROLE_SUPER_ADMIN;
        $name = $user->name;
        $user->delete();

        if ($wasSuperAdminSignup) {
            return redirect()->route('super-admin.pending-approvals.index')
                ->with('success', "Registration for {$name} has been denied and removed. If needed, advise them to use Apply for tenant or ask an existing Super Admin to add a tenant.");
        }

        return redirect()->route('super-admin.pending-approvals.index')
            ->with('success', "Registration for {$name} has been denied and removed.");
    }
}
