<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacController extends Controller
{
    /**
     * List all roles and permissions (RBAC overview).
     */
    public function index(): View
    {
        $roles = Role::with('permissions')->where('guard_name', 'web')->orderBy('name')->get();
        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();

        return view('superadmin.rbac.index', compact('roles', 'permissions'));
    }

    /**
     * Edit a role: assign or revoke permissions.
     */
    public function edit(Role $role): View
    {
        $role->load('permissions');
        $permissions = Permission::where('guard_name', $role->guard_name)->orderBy('name')->get();

        return view('superadmin.rbac.edit-role', compact('role', 'permissions'));
    }

    /**
     * Update the role's permissions.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('super-admin.rbac.index')->with('success', "Permissions updated for role \"{$role->name}\".");
    }
}
