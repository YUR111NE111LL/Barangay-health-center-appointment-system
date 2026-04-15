<?php

namespace App\Http\Controllers\Tenant;

use App\Events\TenantRbacUpdated;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantRbacSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
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

    private const RESERVED_ROLE_NAMES = ['Super Admin'];

    private function roleIsEditableForTenant(Tenant $tenant, Role $role): bool
    {
        if (in_array($role->name, array_merge(self::TENANT_ROLE_NAMES, self::RESERVED_ROLE_NAMES), true)) {
            return ! in_array($role->name, self::RESERVED_ROLE_NAMES, true);
        }

        return DB::table('tenant_role_permissions')
            ->where('tenant_id', $tenant->id)
            ->where('role_name', $role->name)
            ->exists();
    }

    private function ensureBarangayAdmin(): void
    {
        if (Auth::user()?->role !== User::ROLE_HEALTH_CENTER_ADMIN) {
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
                $permissionCount = Permission::query()
                    ->where('guard_name', 'web')
                    ->count();

                if ($permissionCount === 0) {
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
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            abort(403, 'You must belong to a barangay to manage role permissions.');
        }

        if (! $this->ensureRbacReady($tenant)) {
            abort(500, 'Tenant RBAC tables are not ready yet. Please contact the Super Admin.');
        }

        $tenant->load('plan');
        $tenantId = $tenant->id;

        $customRoleNames = DB::table('tenant_role_permissions')
            ->where('tenant_id', $tenantId)
            ->whereNotIn('role_name', array_merge(self::TENANT_ROLE_NAMES, self::RESERVED_ROLE_NAMES))
            ->distinct()
            ->orderBy('role_name')
            ->pluck('role_name')
            ->toArray();

        $roleNames = array_values(array_merge(self::TENANT_ROLE_NAMES, $customRoleNames));
        $roles = Role::where('guard_name', 'web')
            ->whereIn('name', $roleNames)
            ->with('permissions')
            ->orderBy('name')
            ->get();
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

        return view('tenant.rbac.permissions-index', compact('tenant', 'roleNames', 'roles', 'permissionsByRole', 'tenantHasAnyRbac'));
    }

    public function create(): View|RedirectResponse
    {
        $this->ensureBarangayAdmin();
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            abort(403, 'You must belong to a barangay to manage role permissions.');
        }

        if (! $this->ensureRbacReady($tenant)) {
            return redirect()->route('backend.dashboard')
                ->with('error', 'Role management is not ready for this tenant yet. Please contact the Super Admin.');
        }

        $tenant->load('plan');
        $allowedPermissionNames = $this->allowedPermissionsForPlan($tenant->plan?->slug);
        $permissions = Permission::where('guard_name', 'web')
            ->when($allowedPermissionNames !== ['*'] && $allowedPermissionNames !== [], function ($q) use ($allowedPermissionNames) {
                $q->whereIn('name', $allowedPermissionNames);
            })
            ->when($allowedPermissionNames === [], function ($q) {
                $q->whereRaw('1 = 0');
            })
            ->orderBy('name')
            ->get();

        return view('tenant.rbac.permissions-create', compact('tenant', 'permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureBarangayAdmin();
        $tenant = Auth::user()?->tenant;
        if (! $tenant instanceof Tenant) {
            abort(403, 'You must belong to a barangay to manage role permissions.');
        }

        if (! $this->ensureRbacReady($tenant)) {
            return redirect()->route('backend.dashboard')
                ->with('error', 'Role management is not ready for this tenant yet. Please contact the Super Admin.');
        }

        $tenant->load('plan');
        $allowedPermissionNames = $this->allowedPermissionsForPlan($tenant->plan?->slug);
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
            'role_name' => ['required', 'string', 'max:100'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'in:'.implode(',', $permissions)],
        ]);

        $roleName = trim($validated['role_name']);
        if (in_array($roleName, array_merge(self::TENANT_ROLE_NAMES, self::RESERVED_ROLE_NAMES), true)) {
            return back()
                ->withInput()
                ->withErrors(['role_name' => 'This role name is reserved and cannot be created as a custom role.']);
        }

        $existsForTenant = DB::table('tenant_role_permissions')
            ->where('tenant_id', $tenant->id)
            ->whereRaw('LOWER(TRIM(role_name)) = LOWER(?)', [$roleName])
            ->exists();

        $roleModelAlreadyExists = Role::query()
            ->where('guard_name', 'web')
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$roleName])
            ->exists();

        if ($existsForTenant || $roleModelAlreadyExists) {
            return back()
                ->withInput()
                ->withErrors(['role_name' => 'This role already exists for this barangay.']);
        }

        $maxCustomRoles = $this->maxCustomRolesForPlan($tenant->plan?->slug);
        $currentCustomRoleCount = DB::table('tenant_role_permissions')
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('role_name', array_merge(self::TENANT_ROLE_NAMES, self::RESERVED_ROLE_NAMES))
            ->distinct('role_name')
            ->count('role_name');

        if ($currentCustomRoleCount >= $maxCustomRoles) {
            return back()
                ->withInput()
                ->withErrors(['role_name' => "Your current plan allows up to {$maxCustomRoles} custom role(s)."]);
        }

        $toSync = array_values($validated['permissions'] ?? []);
        DB::transaction(function () use ($tenant, $roleName, $toSync): void {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            DB::table('tenant_role_permissions')
                ->where('tenant_id', $tenant->id)
                ->where('role_name', $roleName)
                ->delete();

            foreach ($toSync as $permName) {
                DB::table('tenant_role_permissions')->insert([
                    'tenant_id' => $tenant->id,
                    'role_name' => $roleName,
                    'permission_name' => $permName,
                ]);
            }
        });

        TenantRbacUpdated::dispatch($tenant);

        return redirect()->route('backend.rbac.permissions.index')
            ->with('success', "Role \"{$roleName}\" created successfully.");
    }

    public function edit(Role $role): View|RedirectResponse
    {
        $this->ensureBarangayAdmin();
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            abort(403, 'You must belong to a barangay to manage role permissions.');
        }

        if (! $this->ensureRbacReady($tenant)) {
            return redirect()->route('backend.dashboard')
                ->with('error', 'Role management is not ready for this tenant yet. Please contact the Super Admin.');
        }

        $tenant->load('plan');

        if (! $this->roleIsEditableForTenant($tenant, $role)) {
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

        return view('tenant.rbac.permissions-edit', compact('tenant', 'role', 'permissions', 'currentPermissionNames'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->ensureBarangayAdmin();
        $tenant = Auth::user()?->tenant;
        if (! $tenant instanceof Tenant) {
            abort(403, 'You must belong to a barangay to manage role permissions.');
        }

        if (! $this->ensureRbacReady($tenant)) {
            return redirect()->route('backend.dashboard')
                ->with('error', 'Role management is not ready for this tenant yet. Please contact the Super Admin.');
        }

        $tenant->load('plan');

        if (! $this->roleIsEditableForTenant($tenant, $role)) {
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

        DB::transaction(function () use ($tenant, $role, $toSync): void {
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

        return redirect()->route('backend.rbac.permissions.index')
            ->with('success', "Permissions updated for role \"{$role->name}\" (based on {$planLabel}).");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->ensureBarangayAdmin();
        $tenant = Auth::user()?->tenant;
        if (! $tenant instanceof Tenant) {
            abort(403, 'You must belong to a barangay to manage role permissions.');
        }

        if (! $this->ensureRbacReady($tenant)) {
            return redirect()->route('backend.dashboard')
                ->with('error', 'Role management is not ready for this tenant yet. Please contact the Super Admin.');
        }

        if (in_array($role->name, self::RESERVED_ROLE_NAMES, true) || $role->name === User::ROLE_HEALTH_CENTER_ADMIN) {
            return redirect()->route('backend.rbac.permissions.index')
                ->with('error', 'This role cannot be deleted.');
        }

        if (! $this->roleIsEditableForTenant($tenant, $role)) {
            return redirect()->route('backend.rbac.permissions.index')
                ->with('error', 'Invalid role.');
        }

        $usersUsingRole = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', $role->name)
            ->count();

        DB::table('tenant_role_permissions')
            ->where('tenant_id', $tenant->id)
            ->where('role_name', $role->name)
            ->delete();

        $role->delete();
        TenantRbacUpdated::dispatch($tenant);

        return redirect()->route('backend.rbac.permissions.index')
            ->with('success', "Role \"{$role->name}\" deleted.".($usersUsingRole > 0 ? ' Users currently assigned to this role keep their role name until you reassign them.' : ''));
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

    private function maxCustomRolesForPlan(?string $planSlug): int
    {
        return match ($planSlug ?: 'basic') {
            'premium' => 10,
            'standard' => 5,
            default => 2,
        };
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
