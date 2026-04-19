<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Tenant databases: ensure medicine permissions exist and baseline role grants match {@see RoleAndPermissionSeeder}.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $guard = 'web';
        Permission::firstOrCreate(['name' => 'manage medicine', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'acquire medicine', 'guard_name' => $guard]);

        $healthCenterAdmin = Role::query()->where('name', 'Health Center Admin')->where('guard_name', $guard)->first();
        if ($healthCenterAdmin) {
            $healthCenterAdmin->givePermissionTo('manage medicine');
        }

        $resident = Role::query()->where('name', 'Resident')->where('guard_name', $guard)->first();
        if ($resident) {
            $resident->givePermissionTo('acquire medicine');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        // Do not revoke role assignments or delete permissions — other tenant data may reference them.
    }
};
