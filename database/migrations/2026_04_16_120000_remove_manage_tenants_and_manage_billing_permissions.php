<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private const NAMES = ['manage tenants', 'manage billing'];

    /**
     * Remove unused Spatie permissions (Super Admin access is role-based, not via these).
     */
    public function up(): void
    {
        if (Schema::hasTable('tenant_role_permissions')) {
            DB::table('tenant_role_permissions')
                ->whereIn('permission_name', self::NAMES)
                ->delete();
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::NAMES)
            ->delete();
    }

    /**
     * Restore permissions for environments that need to roll back.
     */
    public function down(): void
    {
        $guard = 'web';
        foreach (self::NAMES as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $superAdmin = Role::query()
            ->where('guard_name', $guard)
            ->where('name', 'Super Admin')
            ->first();

        if ($superAdmin !== null) {
            $superAdmin->givePermissionTo(self::NAMES);
        }
    }
};
