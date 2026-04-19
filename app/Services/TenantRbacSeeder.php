<?php

namespace App\Services;

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

    public static function seedTenant(int $tenantId): void
    {
        if (! Schema::hasTable('tenant_role_permissions')) {
            return;
        }

        self::ensureSpatieSeededForTenantDatabase();

        $hasAnyRows = DB::table('tenant_role_permissions')->where('tenant_id', $tenantId)->exists();

        if ($hasAnyRows) {
            if (! isset(self::$baselineFilledForTenant[$tenantId])) {
                self::fillMissingStandardRoleBaselines($tenantId);
                self::$baselineFilledForTenant[$tenantId] = true;
            }

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
    private static function fillMissingStandardRoleBaselines(int $tenantId): void
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

            foreach ($role->permissions->pluck('name') as $permName) {
                DB::table('tenant_role_permissions')->insertOrIgnore([
                    'tenant_id' => $tenantId,
                    'role_name' => $role->name,
                    'permission_name' => $permName,
                ]);
            }
        }
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
