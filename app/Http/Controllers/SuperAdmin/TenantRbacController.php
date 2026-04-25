<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Events\TenantRbacUpdated;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantRbacSeeder;
use App\Support\TenantRbacExcludedPermissions;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
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

    private const REQUIRED_RBAC_TABLES = [
        'roles',
        'permissions',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'tenant_role_permissions',
    ];

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
        $tablesReady = false;
        $tenant->run(function () use (&$tablesReady): void {
            $tablesReady = $this->hasRequiredRbacTables();
        });

        if (! $tablesReady) {
            try {
                Artisan::call('tenants:migrate', [
                    '--tenants' => [$tenant->getTenantKey()],
                    '--force' => true,
                ]);
            } catch (\Throwable $e) {
                return false;
            }
        }

        try {
            $tenant->run(function () use ($tenant): void {
                if (! $this->hasRequiredRbacTables()) {
                    throw new \RuntimeException('Tenant RBAC tables missing.');
                }

                $roleCount = Role::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', self::TENANT_ROLE_NAMES)
                    ->count();

                $permissionCount = Permission::query()
                    ->where('guard_name', 'web')
                    ->count();

                if ($roleCount < count(self::TENANT_ROLE_NAMES) || $permissionCount === 0) {
                    (new RoleAndPermissionSeeder)->run();
                } else {
                    RoleAndPermissionSeeder::syncPermissionTable();
                }

                TenantRbacSeeder::seedTenant((int) $tenant->id, $tenant->plan?->slug);
            });
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    public function index(Tenant $tenant): View
    {
        if (! $this->ensureRbacReady($tenant)) {
            abort(500, 'Tenant RBAC tables are not ready yet. Please retry provisioning.');
        }

        $tenant->load('plan');
        $roleNames = self::TENANT_ROLE_NAMES;
        $permissionsByRole = [];
        $roles = collect();
        $tenantHasAnyRbac = false;

        $tenant->run(function () use (&$roles, &$tenantHasAnyRbac, &$permissionsByRole, $roleNames, $tenant): void {
            $roles = Role::where('guard_name', 'web')->whereIn('name', $roleNames)->with('permissions')->orderBy('name')->get();
            $tenantHasAnyRbac = Schema::hasTable('tenant_role_permissions') && DB::table('tenant_role_permissions')->where('tenant_id', $tenant->id)->exists();
            foreach ($roleNames as $roleName) {
                $fromTable = DB::table('tenant_role_permissions')
                    ->where('tenant_id', $tenant->id)
                    ->where('role_name', $roleName)
                    ->pluck('permission_name')
                    ->toArray();
                if ($fromTable !== []) {
                    $permissionsByRole[$roleName] = TenantRbacExcludedPermissions::filterList($fromTable);
                } else {
                    $roleModel = $roles->firstWhere('name', $roleName);
                    $permissionsByRole[$roleName] = $this->defaultPermissionsForRole($tenant, $roleName, $roleModel);
                }
            }
        });

        return view('superadmin.tenants.rbac.index', compact('tenant', 'roleNames', 'roles', 'permissionsByRole', 'tenantHasAnyRbac'));
    }

    public function edit(Tenant $tenant, Role $role): View|RedirectResponse
    {
        if (! $this->ensureRbacReady($tenant)) {
            return redirect()->route('super-admin.tenants.show', $tenant)
                ->with('error', 'Role management is not ready for this tenant yet. Please retry provisioning.');
        }

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
        $permissions = collect();
        $currentPermissionNames = [];
        $tenantHasAnyRbac = false;
        $excluded = TenantRbacExcludedPermissions::names();
        $tenant->run(function () use (&$permissions, &$currentPermissionNames, &$tenantHasAnyRbac, $allowedPermissionNames, $tenant, $role, $excluded): void {
            $permissions = Permission::where('guard_name', 'web')
                ->when($excluded !== [], fn ($q) => $q->whereNotIn('name', $excluded))
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
            if ($currentPermissionNames === []) {
                $fillDefaults = ! $tenantHasAnyRbac || in_array($role->name, self::TENANT_ROLE_NAMES, true);
                if ($fillDefaults) {
                    $currentPermissionNames = $this->defaultPermissionsForRole($tenant, $role->name, $role);
                }
            }
        });

        return view('superadmin.tenants.rbac.edit', compact('tenant', 'role', 'permissions', 'currentPermissionNames'));
    }

    public function update(Request $request, Tenant $tenant, Role $role): RedirectResponse
    {
        if (! $this->ensureRbacReady($tenant)) {
            return redirect()->route('super-admin.tenants.show', $tenant)
                ->with('error', 'Role management is not ready for this tenant yet. Please retry provisioning.');
        }

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
        $permissions = [];
        $excluded = TenantRbacExcludedPermissions::names();
        $tenant->run(function () use (&$permissions, $allowedPermissionNames, $excluded): void {
            $permissions = Permission::where('guard_name', 'web')
                ->when($excluded !== [], fn ($q) => $q->whereNotIn('name', $excluded))
                ->when($allowedPermissionNames !== ['*'] && $allowedPermissionNames !== [], function ($q) use ($allowedPermissionNames) {
                    $q->whereIn('name', $allowedPermissionNames);
                })
                ->when($allowedPermissionNames === [], function ($q) {
                    $q->whereRaw('1 = 0');
                })
                ->pluck('name')
                ->toArray();
        });

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissions)],
        ]);

        $toSync = array_values($validated['permissions'] ?? []);

        $tenant->run(function () use ($tenant, $role, $toSync): void {
            if (Schema::hasTable('tenant_role_permissions') && ! DB::table('tenant_role_permissions')->where('tenant_id', $tenant->id)->exists()) {
                TenantRbacSeeder::seedTenant((int) $tenant->id, $tenant->plan?->slug);
            }

            DB::table('tenant_role_permissions')->where('tenant_id', $tenant->id)->where('role_name', $role->name)->delete();
            foreach ($toSync as $permName) {
                DB::table('tenant_role_permissions')->insert([
                    'tenant_id' => $tenant->id,
                    'role_name' => $role->name,
                    'permission_name' => $permName,
                ]);
            }
        });

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

    private function defaultPermissionsForRole(Tenant $tenant, string $roleName, ?Role $roleModel): array
    {
        $defaults = $roleModel ? $roleModel->permissions->pluck('name')->toArray() : [];
        $allowedByPlan = $this->allowedPermissionsForPlan($tenant->plan?->slug);

        if ($allowedByPlan !== ['*']) {
            $defaults = array_values(array_intersect($defaults, $allowedByPlan));
        }

        if ($roleName === 'Resident') {
            $residentPerms = config('bhcas.resident_role_permissions', ['book appointments']);
            $defaults = array_values(array_intersect($defaults, $residentPerms));
        }

        return TenantRbacExcludedPermissions::filterList($defaults);
    }
}
