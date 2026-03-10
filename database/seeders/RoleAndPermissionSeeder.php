<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        Permission::firstOrCreate(['name' => 'manage tenants', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'manage billing', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'manage schedules', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'approve appointments', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'view reports', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'view appointments', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'update visit status', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'record notes', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'encode appointments', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'book appointments', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'manage inventory', 'guard_name' => $guard]);

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
        $superAdmin->givePermissionTo(['manage tenants', 'manage billing', 'view reports']);

        $healthCenterAdmin = Role::firstOrCreate(['name' => 'Health Center Admin', 'guard_name' => $guard]);
        $healthCenterAdmin->givePermissionTo(['manage schedules', 'approve appointments', 'view reports', 'view appointments', 'encode appointments']);

        $nurse = Role::firstOrCreate(['name' => 'Nurse', 'guard_name' => $guard]);
        $nurse->givePermissionTo(['view appointments', 'update visit status', 'record notes']);

        $staff = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => $guard]);
        $staff->givePermissionTo(['view appointments', 'encode appointments']);

        $resident = Role::firstOrCreate(['name' => 'Resident', 'guard_name' => $guard]);
        $resident->givePermissionTo(['book appointments']);
    }
}
