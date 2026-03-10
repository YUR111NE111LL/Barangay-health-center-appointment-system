<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
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
            ->with('tenant')
            ->get();

        return view('superadmin.pending-approvals.index', compact('pending'));
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        if (! in_array($user->role, User::rolesApprovedBySuperAdmin(), true) || $user->is_approved) {
            return redirect()->route('super-admin.pending-approvals.index')
                ->with('error', 'That account is not pending approval.');
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
        $name = $user->name;
        $user->delete();
        return redirect()->route('super-admin.pending-approvals.index')
            ->with('success', "Registration for {$name} has been denied and removed.");
    }
}
