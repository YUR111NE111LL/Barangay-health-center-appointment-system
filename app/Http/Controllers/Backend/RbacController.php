<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * RBAC for Barangay Admin only: assign roles to users within the tenant.
 * Nurses and Residents cannot view or access RBAC; permissions are per-tenant and based on the tenant's plan.
 */
class RbacController extends Controller
{
    /** Ensure only Health Center Admin can access (defence-in-depth; routes also use role middleware). */
    private function ensureBarangayAdmin(): void
    {
        if (auth()->user()->role !== User::ROLE_HEALTH_CENTER_ADMIN) {
            abort(403, 'Only Barangay (Health Center) Admin can view and manage roles. Nurses and Residents do not have access.');
        }
    }

    public function index(): View
    {
        $this->ensureBarangayAdmin();
        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('backend.rbac.index', compact('users'));
    }

    public function edit(User $user): View
    {
        $this->ensureBarangayAdmin();
        // Ensure user belongs to same tenant
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'You can only assign roles to users in your barangay.');
        }

        $roles = [
            User::ROLE_RESIDENT => 'Resident (Patient)',
            User::ROLE_STAFF => 'Staff',
            User::ROLE_NURSE => 'Nurse / Midwife',
            User::ROLE_HEALTH_CENTER_ADMIN => 'Health Center Admin',
        ];

        return view('backend.rbac.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureBarangayAdmin();
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'You can only assign roles to users in your barangay.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_RESIDENT, User::ROLE_STAFF, User::ROLE_NURSE, User::ROLE_HEALTH_CENTER_ADMIN])],
        ]);

        $user->update(['role' => $validated['role']]);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('backend.rbac.index')->with('success', "Role updated for {$user->name}.");
    }
}
