<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Ensure all application permission rows exist (idempotent). Call after upgrades
     * so new permissions appear in Role permissions checkboxes without wiping the DB.
     */
    public static function syncPermissionTable(): void
    {
        $guard = 'web';

        foreach (self::permissionNames() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return [
            'manage schedules',
            'approve appointments',
            'view reports',
            'view appointments',
            'update visit status',
            'record notes',
            'encode appointments',
            'book appointments',
            'manage inventory',
            'manage medicine',
            'acquire medicine',
        ];
    }

    public function run(): void
    {
        self::syncPermissionTable();

        $guard = 'web';

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
        $superAdmin->givePermissionTo(['view reports']);

        $healthCenterAdmin = Role::firstOrCreate(['name' => 'Health Center Admin', 'guard_name' => $guard]);
        $healthCenterAdmin->givePermissionTo(['manage schedules', 'approve appointments', 'view reports', 'view appointments', 'encode appointments', 'manage medicine']);

        $nurse = Role::firstOrCreate(['name' => 'Nurse', 'guard_name' => $guard]);
        $nurse->givePermissionTo(['view appointments', 'update visit status', 'record notes']);

        $staff = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => $guard]);
        $staff->givePermissionTo(['view appointments', 'encode appointments']);

        $resident = Role::firstOrCreate(['name' => 'Resident', 'guard_name' => $guard]);
        $resident->givePermissionTo(['book appointments', 'acquire medicine']);
    }
}
