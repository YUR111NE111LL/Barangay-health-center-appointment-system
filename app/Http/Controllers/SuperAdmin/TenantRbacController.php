<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Events\TenantRbacUpdated;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantRbacSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RBAC per tenant: configure role permissions per tenant, filtered by the tenant's plan.
 */
class TenantRbacController extends Controller
{
    /** Tenant roles that can have permissions (exclude Super Admin). */
    private const TENANT_ROLE_NAMES = ['Health Center Admin', 'Nurse', 'Staff', 'Resident'];

    public function index(Tenant $tenant): View
    {
        $tenant->load('plan');
        $roleNames = self::TENANT_ROLE_NAMES;
        $roles = Role::where('guard_name', 'web')->whereIn('name', $roleNames)->with('permissions')->orderBy('name')->get();
        $tenantHasAnyRbac = Schema::hasTable('tenant_role_permissions') && DB::table('tenant_role_permissions')->where('tenant_id', $tenant->id)->exists();
        $permissionsByRole = [];
        foreach ($roleNames as $roleName) {
            $fromTable = DB::table('tenant_role_permissions')
                ->where('tenant_id', $tenant->id)
                ->where('role_name', $roleName)
                ->pluck('permission_name')
                ->toArray();
            if ($fromTable !== []) {
                $permissionsByRole[$roleName] = $fromTable;
            } else {
                $roleModel = $roles->firstWhere('name', $roleName);
                $permissionsByRole[$roleName] = $tenantHasAnyRbac ? [] : ($roleModel ? $roleModel->permissions->pluck('name')->toArray() : []);
            }
        }

        return view('superadmin.tenants.rbac.index', compact('tenant', 'roleNames', 'roles', 'permissionsByRole', 'tenantHasAnyRbac'));
    }

    public function edit(Tenant $tenant, Role $role): View|RedirectResponse
    {
        $tenant->load('plan');
        if (! in_array($role->name, self::TENANT_ROLE_NAMES, true)) {
            return redirect()->route('super-admin.tenants.rbac.index', $tenant)
                ->with('error', 'That role cannot be edited for tenants.');
        }

        $allowedPermissionNames = $this->allowedPermissionsForPlan($tenant->plan?->slug);
        if ($role->name === 'Resident') {
            $residentPerms = config('bhcas.resident_role_permissions', ['book appointments']);
            $allowedPermissionNames = $allowedPermissionNames === ['*']
                ? $residentPerms
                : array_values(array_intersect($allowedPermissionNames, $residentPerms));
        }
        $permissions = Permission::where('guard_name', 'web')
            ->when($allowedPermissionNames !== ['*'] && $allowedPermissionNames !== [], function ($q) use ($allowedPermissionNames) {
                $q->whereIn('name', $allowedPermissionNames);
            })
            ->when($allowedPermissionNames === [], function ($q) {
                $q->whereRaw('1 = 0');
            })
            ->orderBy('name')
            ->get();

        $currentPermissionNames = DB::table('tenant_role_permissions')
            ->where('tenant_id', $tenant->id)
            ->where('role_name', $role->name)
            ->pluck('permission_name')
            ->toArray();

        $tenantHasAnyRbac = Schema::hasTable('tenant_role_permissions') && DB::table('tenant_role_permissions')->where('tenant_id', $tenant->id)->exists();
        if ($currentPermissionNames === [] && ! $tenantHasAnyRbac) {
            $currentPermissionNames = $role->permissions->pluck('name')->toArray();
        }

        return view('superadmin.tenants.rbac.edit', compact('tenant', 'role', 'permissions', 'currentPermissionNames'));
    }

    public function update(Request $request, Tenant $tenant, Role $role): RedirectResponse
    {
        $tenant->load('plan');
        if (! in_array($role->name, self::TENANT_ROLE_NAMES, true)) {
            return redirect()->route('super-admin.tenants.rbac.index', $tenant)->with('error', 'Invalid role.');
        }

        $allowedPermissionNames = $this->allowedPermissionsForPlan($tenant->plan?->slug);
        if ($role->name === 'Resident') {
            $residentPerms = config('bhcas.resident_role_permissions', ['book appointments']);
            $allowedPermissionNames = $allowedPermissionNames === ['*']
                ? $residentPerms
                : array_values(array_intersect($allowedPermissionNames, $residentPerms));
        }
        $permissions = Permission::where('guard_name', 'web')
            ->when($allowedPermissionNames !== ['*'] && $allowedPermissionNames !== [], function ($q) use ($allowedPermissionNames) {
                $q->whereIn('name', $allowedPermissionNames);
            })
            ->when($allowedPermissionNames === [], function ($q) {
                $q->whereRaw('1 = 0');
            })
            ->pluck('name')
            ->toArray();

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', $permissions)],
        ]);

        $toSync = array_values($validated['permissions'] ?? []);

        if (Schema::hasTable('tenant_role_permissions') && ! DB::table('tenant_role_permissions')->where('tenant_id', $tenant->id)->exists()) {
            TenantRbacSeeder::seedTenant($tenant->id);
        }

        DB::table('tenant_role_permissions')->where('tenant_id', $tenant->id)->where('role_name', $role->name)->delete();
        foreach ($toSync as $permName) {
            DB::table('tenant_role_permissions')->insert([
                'tenant_id' => $tenant->id,
                'role_name' => $role->name,
                'permission_name' => $permName,
            ]);
        }

        TenantRbacUpdated::dispatch($tenant);

        $planLabel = $tenant->plan ? $tenant->plan->name : 'plan';
        return redirect()->route('super-admin.tenants.rbac.index', $tenant)
            ->with('success', "Permissions updated for role \"{$role->name}\" (based on {$planLabel}).");
    }

    private function allowedPermissionsForPlan(?string $planSlug): array
    {
        $planSlug = $planSlug ?: 'basic';
        $map = config('bhcas.plan_permissions', []);
        $allowed = $map[$planSlug] ?? $map['basic'] ?? ['*'];
        if ($allowed === ['*']) {
            return ['*'];
        }
        return $allowed;
    }

}
