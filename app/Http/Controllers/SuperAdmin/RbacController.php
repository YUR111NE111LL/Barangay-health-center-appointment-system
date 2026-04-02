<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RbacController extends Controller
{
    /**
     * Global Super Admin RBAC UI is not used; barangay RBAC is configured per tenant (Tenants → RBAC).
     * These routes redirect so bookmarks still land in the right place.
     */
    public function index(): RedirectResponse
    {
        return redirect()
            ->route('super-admin.tenants.index')
            ->with('info', __('Configure role permissions per barangay: open a tenant and use RBAC. There is no global Roles & Permissions page for Super Admin.'));
    }

    public function edit(Role $role): RedirectResponse
    {
        return $this->index();
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        return $this->index();
    }
}
