<?php

namespace App\Services;

use App\Support\TenantRbacExcludedPermissions;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed tenant_role_permissions from global Spatie role permissions (default RBAC).
 * Ensures every tenant has rows so Gate can enforce only what is checked.
 *
 * New tenant databases only run migrations — they do not run DatabaseSeeder, so Spatie
 * roles/permissions tables can be empty. We ensure {@see RoleAndPermissionSeeder} has
 * run in the tenant DB before copying into tenant_role_permissions.
 */
class TenantRbacSeeder
{
    private const TENANT_ROLE_NAMES = ['Health Center Admin', 'Nurse', 'Staff', 'Resident'];

    /**
     * @var array<int, true> Request-local memo: baseline backfill already ran for this tenant id.
     */
    private static array $baselineFilledForTenant = [];

    public static function seedTenant(int $tenantId, ?string $planSlug = null): void
    {
        if (! Schema::hasTable('tenant_role_permissions')) {
            return;
        }

        self::ensureSpatieSeededForTenantDatabase();

        $hasAnyRows = DB::table('tenant_role_permissions')->where('tenant_id', $tenantId)->exists();

        if ($hasAnyRows) {
            if (! isset(self::$baselineFilledForTenant[$tenantId])) {
                self::fillMissingStandardRoleBaselines($tenantId, $planSlug);
                self::$baselineFilledForTenant[$tenantId] = true;
            }

            return;
        }

        $normalizedPlanSlug = strtolower((string) ($planSlug ?? ''));
        if ($normalizedPlanSlug !== '') {
            self::syncStandardRolesToPlanDefaults($tenantId, $normalizedPlanSlug);

            return;
        }

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::TENANT_ROLE_NAMES)
            ->with('permissions')
            ->get();

        foreach ($roles as $role) {
            foreach ($role->permissions->pluck('name') as $permName) {
                DB::table('tenant_role_permissions')->insertOrIgnore([
                    'tenant_id' => $tenantId,
                    'role_name' => $role->name,
                    'permission_name' => $permName,
                ]);
            }
        }
    }

    /**
     * Tenant DBs are provisioned with migrations only; Spatie permission/role rows may be missing.
     */
    private static function ensureSpatieSeededForTenantDatabase(): void
    {
        $permTable = config('permission.table_names.permissions', 'permissions');
        if (! Schema::hasTable($permTable)) {
            return;
        }

        $permCount = Permission::query()->where('guard_name', 'web')->count();
        $rolesReady = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::TENANT_ROLE_NAMES)
            ->count() >= count(self::TENANT_ROLE_NAMES);

        if ($permCount === 0 || ! $rolesReady) {
            (new RoleAndPermissionSeeder)->run();

            return;
        }

        RoleAndPermissionSeeder::syncPermissionTable();
    }

    /**
     * If this tenant has RBAC rows but a standard role has none (e.g. partial seed), copy Spatie defaults for that role only.
     */
    private static function fillMissingStandardRoleBaselines(int $tenantId, ?string $planSlug = null): void
    {
        foreach (self::TENANT_ROLE_NAMES as $roleName) {
            $hasRows = DB::table('tenant_role_permissions')
                ->where('tenant_id', $tenantId)
                ->where('role_name', $roleName)
                ->exists();

            if ($hasRows) {
                continue;
            }

            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', $roleName)
                ->with('permissions')
                ->first();

            if ($role === null) {
                continue;
            }

            $permissionNames = self::permissionsForRoleWithPlanFilter(
                $role->permissions->pluck('name')->toArray(),
                $roleName,
                $planSlug
            );

            foreach ($permissionNames as $permName) {
                DB::table('tenant_role_permissions')->insertOrIgnore([
                    'tenant_id' => $tenantId,
                    'role_name' => $role->name,
                    'permission_name' => $permName,
                ]);
            }
        }
    }

    public static function syncStandardRolesToPlanDefaults(int $tenantId, ?string $planSlug): void
    {
        if (! Schema::hasTable('tenant_role_permissions')) {
            return;
        }

        self::ensureSpatieSeededForTenantDatabase();

        $normalizedPlanSlug = strtolower((string) ($planSlug ?? 'basic'));

        foreach (self::TENANT_ROLE_NAMES as $roleName) {
            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', $roleName)
                ->with('permissions')
                ->first();

            if ($role === null) {
                continue;
            }

            $permissionNames = self::permissionsForRoleWithPlanFilter(
                $role->permissions->pluck('name')->toArray(),
                $roleName,
                $normalizedPlanSlug
            );

            DB::table('tenant_role_permissions')
                ->where('tenant_id', $tenantId)
                ->where('role_name', $roleName)
                ->delete();

            foreach ($permissionNames as $permName) {
                DB::table('tenant_role_permissions')->insertOrIgnore([
                    'tenant_id' => $tenantId,
                    'role_name' => $roleName,
                    'permission_name' => $permName,
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $permissionNames
     * @return list<string>
     */
    private static function permissionsForRoleWithPlanFilter(array $permissionNames, string $roleName, ?string $planSlug): array
    {
        $filtered = TenantRbacExcludedPermissions::filterList($permissionNames);
        $allowedByPlan = self::allowedPermissionsForPlan($planSlug);

        if ($allowedByPlan !== ['*']) {
            $filtered = array_values(array_intersect($filtered, $allowedByPlan));
        }

        if ($roleName === 'Resident') {
            $residentPerms = config('bhcas.resident_role_permissions', ['book appointments']);
            $filtered = array_values(array_intersect($filtered, $residentPerms));
        }

        $filtered = array_values(array_unique($filtered));
        sort($filtered);

        return $filtered;
    }

    /**
     * @return list<string>
     */
    private static function allowedPermissionsForPlan(?string $planSlug): array
    {
        $normalizedPlanSlug = strtolower((string) ($planSlug ?: 'basic'));
        $map = config('bhcas.plan_permissions', []);
        $allowed = $map[$normalizedPlanSlug] ?? $map['basic'] ?? ['*'];

        return $allowed === ['*'] ? ['*'] : array_values($allowed);
    }

    /** Seed all tenants that have no tenant_role_permissions rows. */
    public static function seedAllTenants(): int
    {
        $tenantIds = DB::table('tenants')->pluck('id');
        $count = 0;
        foreach ($tenantIds as $tenantId) {
            $exists = DB::table('tenant_role_permissions')->where('tenant_id', $tenantId)->exists();
            if (! $exists) {
                self::seedTenant((int) $tenantId);
                $count++;
            }
        }

        return $count;
    }
}
