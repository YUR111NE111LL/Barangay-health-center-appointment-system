<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendingApprovalsController extends Controller
{
    /**
     * List Staff and Nurse signups pending Barangay Admin approval (same tenant only).
     */
    public function index(): View
    {
        $tenantId = auth()->user()->tenant_id;
        $pending = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('role', User::rolesApprovedByBarangayAdmin())
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tenant.pending-approvals.index', compact('pending'));
    }

    /**
     * Approve a pending Staff or Nurse.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            return redirect()->route('backend.pending-approvals.index')
                ->with('error', 'Access denied.');
        }
        if (! in_array($user->role, User::rolesApprovedByBarangayAdmin(), true) || $user->is_approved) {
            return redirect()->route('backend.pending-approvals.index')
                ->with('error', 'That account is not pending approval.');
        }

        $user->update(['is_approved' => true]);

        return redirect()->route('backend.pending-approvals.index')
            ->with('success', "{$user->name} ({$user->role}) has been approved. They can now log in.");
    }

    /**
     * Deny and remove a pending Staff or Nurse.
     */
    public function deny(Request $request, User $user): RedirectResponse
    {
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            return redirect()->route('backend.pending-approvals.index')
                ->with('error', 'Access denied.');
        }
        if (! in_array($user->role, User::rolesApprovedByBarangayAdmin(), true) || $user->is_approved) {
            return redirect()->route('backend.pending-approvals.index')
                ->with('error', 'That account is not pending approval.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('backend.pending-approvals.index')
            ->with('success', "Registration for {$name} has been denied and removed.");
    }
}
