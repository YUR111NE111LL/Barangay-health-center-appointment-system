<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Seed tenant_role_permissions from global Spatie role permissions (default RBAC).
 * Ensures every tenant has rows so Gate can enforce only what is checked.
 */
class TenantRbacSeeder
{
    private const TENANT_ROLE_NAMES = ['Health Center Admin', 'Nurse', 'Staff', 'Resident'];

    public static function seedTenant(int $tenantId): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('tenant_role_permissions')) {
            return;
        }
        $exists = DB::table('tenant_role_permissions')->where('tenant_id', $tenantId)->exists();
        if ($exists) {
            return;
        }
        $roles = Role::where('guard_name', 'web')->whereIn('name', self::TENANT_ROLE_NAMES)->with('permissions')->get();
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
