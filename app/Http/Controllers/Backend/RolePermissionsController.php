<?php

namespace App\Http\Controllers\Backend;

use App\Events\TenantRbacUpdated;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantRbacSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Barangay Admin only: configure which permissions each role has for this tenant (same as Super Admin per-tenant RBAC, but for own tenant only).
 * Permissions are limited by the tenant's plan; changes apply only to this barangay.
 */
class RolePermissionsController extends Controller
{
    private const TENANT_ROLE_NAMES = ['Health Center Admin', 'Nurse', 'Staff', 'Resident'];

    private const REQUIRED_RBAC_TABLES = [
        'roles',
        'permissions',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'tenant_role_permissions',
    ];

    private function ensureBarangayAdmin(): void
    {
        if (auth()->user()->role !== User::ROLE_HEALTH_CENTER_ADMIN) {
            abort(403, 'Only Barangay (Health Center) Admin can manage role permissions.');
        }
    }

    private function hasRequiredRbacTables(): bool
    {
        foreach (self::REQUIRED_RBAC_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function ensureRbacReady(Tenant $tenant): bool
    {
        if ($this->hasRequiredRbacTables()) {
            return $this->ensureRbacSeedData($tenant);
        }

        try {
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->getTenantKey()],
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        if (! $this->hasRequiredRbacTables()) {
            return false;
        }

        return $this->ensureRbacSeedData($tenant);
    }

    private function ensureRbacSeedData(Tenant $tenant): bool
    {
        try {
            $tenant->run(function () use ($tenant): void {
                $roleCount = Role::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', self::TENANT_ROLE_NAMES)
                    ->count();

                $permissionCount = Permission::query()
                    ->where('guard_name', 'web')
                    ->count();

                if ($roleCount < count(self::TENANT_ROLE_NAMES) || $permissionCount === 0) {
                    (new RoleAndPermissionSeeder)->run();
                }

                TenantRbacSeeder::seedTenant((int) $tenant->id);
            });
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /** List roles and their current permissions; link to edit each. */
    public function index(): View
    {
        $this->ensureBarangayAdmin();
        $tenant = auth()->user()->tenant;
        if (! $tenant) {
            abort(403, 'You must belong to a barangay to manage role permissions.');
        }

        if (! $this->ensureRbacReady($tenant)) {
            abort(500, 'Tenant RBAC tables are not ready yet. Please contact the Super Admin.');
        }

        $tenant->load('plan');

        $roleNames = self::TENANT_ROLE_NAMES;
        $roles = Role::where('guard_name', 'web')->whereIn('name', $roleNames)->with('permissions')->orderBy('name')->get();
        $tenantId = $tenant->id;
        $tenantHasAnyRbac = Schema::hasTable('tenant_role_permissions') && DB::table('tenant_role_permissions')->where('tenant_id', $tenantId)->exists();
        $permissionsByRole = [];
        foreach ($roleNames as $roleName) {
            $fromTable = DB::table('tenant_role_permissions')
                ->where('tenant_id', $tenantId)
                ->where('role_name', $roleName)
                ->pluck('permission_name')
                ->toArray();
            if ($fromTable !== []) {
                $permissionsByRole[$roleName] = $fromTable;
            } else {
                $roleModel = $roles->firstWhere('name', $roleName);
                $permissionsByRole[$roleName] = $tenantHasAnyRbac
                    ? []
                    : $this->defaultPermissionsForRole($tenant, $roleName, $roleModel);
            }
        }

        return view('backend.rbac.permissions-index', compact('tenant', 'roleNames', 'roles', 'permissionsByRole', 'tenantHasAnyRbac'));
    }

    public function edit(Role $role): View|RedirectResponse
    {
        $this->ensureBarangayAdmin();
        $tenant = auth()->user()->tenant;
        if (! $tenant) {
            abort(403, 'You must belong to a barangay to manage role permissions.');
        }

        if (! $this->ensureRbacReady($tenant)) {
            return redirect()->route('backend.dashboard')
                ->with('error', 'Role management is not ready for this tenant yet. Please contact the Super Admin.');
        }

        $tenant->load('plan');

        if (! in_array($role->name, self::TENANT_ROLE_NAMES, true)) {
            return redirect()->route('backend.rbac.permissions.index')
                ->with('error', 'That role cannot be edited.');
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
            $currentPermissionNames = $this->defaultPermissionsForRole($tenant, $role->name, $role);
        }

        return view('backend.rbac.permissions-edit', compact('tenant', 'role', 'permissions', 'currentPermissionNames'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->ensureBarangayAdmin();
        $tenant = auth()->user()->tenant;
        if (! $tenant instanceof Tenant) {
            abort(403, 'You must belong to a barangay to manage role permissions.');
        }

        if (! $this->ensureRbacReady($tenant)) {
            return redirect()->route('backend.dashboard')
                ->with('error', 'Role management is not ready for this tenant yet. Please contact the Super Admin.');
        }

        $tenant->load('plan');

        if (! in_array($role->name, self::TENANT_ROLE_NAMES, true)) {
            return redirect()->route('backend.rbac.permissions.index')->with('error', 'Invalid role.');
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
            'permissions.*' => ['string', 'in:'.implode(',', $permissions)],
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

        return redirect()->route('backend.rbac.permissions.index')
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

    private function defaultPermissionsForRole(Tenant $tenant, string $roleName, ?Role $roleModel): array
    {
        $defaults = $roleModel ? $roleModel->permissions->pluck('name')->toArray() : [];
        $allowedByPlan = $this->allowedPermissionsForPlan($tenant->plan?->slug);

        if ($allowedByPlan !== ['*']) {
            $defaults = array_values(array_intersect($defaults, $allowedByPlan));
        }

        if ($roleName === User::ROLE_RESIDENT) {
            $residentPerms = config('bhcas.resident_role_permissions', ['book appointments']);
            $defaults = array_values(array_intersect($defaults, $residentPerms));
        }

        return $defaults;
    }
}
