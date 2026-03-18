<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $sumpong = Tenant::whereHas('domains', fn ($q) => $q->where('domain', 'brgy-sumpong.test'))->first();
        $casisang = Tenant::whereHas('domains', fn ($q) => $q->where('domain', 'brgy-casisang.test'))->first();
        $premiumTenant = Tenant::whereHas('domains', fn ($q) => $q->where('domain', 'brgy-kalasungay.test'))->first();

        // Super Admin (no tenant)
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@bhcas.test'],
            [
                'tenant_id' => null,
                'role' => 'Super Admin',
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->syncRoles(['Super Admin']);

        if ($sumpong) {
            $admin = User::firstOrCreate(
                ['tenant_id' => $sumpong->id, 'email' => 'admin@sumpong.test'],
                [
                    'role' => 'Health Center Admin',
                    'name' => 'Sumpong Admin',
                    'password' => Hash::make('password'),
                ]
            );
            $admin->syncRoles(['Health Center Admin']);

            $nurse = User::firstOrCreate(
                ['tenant_id' => $sumpong->id, 'email' => 'nurse@sumpong.test'],
                [
                    'role' => 'Nurse',
                    'name' => 'Nurse Sumpong',
                    'password' => Hash::make('password'),
                ]
            );
            $nurse->syncRoles(['Nurse']);

            $staff = User::firstOrCreate(
                ['tenant_id' => $sumpong->id, 'email' => 'staff@sumpong.test'],
                [
                    'role' => 'Staff',
                    'name' => 'Staff Sumpong',
                    'password' => Hash::make('password'),
                ]
            );
            $staff->syncRoles(['Staff']);

            $resident = User::firstOrCreate(
                ['tenant_id' => $sumpong->id, 'email' => 'resident@sumpong.test'],
                [
                    'role' => 'Resident',
                    'name' => 'Juan Dela Cruz',
                    'password' => Hash::make('password'),
                ]
            );
            $resident->syncRoles(['Resident']);
        }

        if ($casisang) {
            $admin2 = User::firstOrCreate(
                ['tenant_id' => $casisang->id, 'email' => 'admin@casisang.test'],
                [
                    'role' => 'Health Center Admin',
                    'name' => 'Casisang Admin',
                    'password' => Hash::make('password'),
                ]
            );
            $admin2->syncRoles(['Health Center Admin']);

            $resident2 = User::firstOrCreate(
                ['tenant_id' => $casisang->id, 'email' => 'resident@casisang.test'],
                [
                    'role' => 'Resident',
                    'name' => 'Maria Santos',
                    'password' => Hash::make('password'),
                ]
            );
            $resident2->syncRoles(['Resident']);
        }

        if ($premiumTenant) {
            $premiumAdmin = User::firstOrCreate(
                ['tenant_id' => $premiumTenant->id, 'email' => 'admin@kalasungay.test'],
                [
                    'role' => 'Health Center Admin',
                    'name' => 'Kalasungay Admin',
                    'password' => Hash::make('password'),
                ]
            );
            $premiumAdmin->syncRoles(['Health Center Admin']);

            $premiumResident = User::firstOrCreate(
                ['tenant_id' => $premiumTenant->id, 'email' => 'resident@kalasungay.test'],
                [
                    'role' => 'Resident',
                    'name' => 'Pedro Reyes',
                    'password' => Hash::make('password'),
                ]
            );
            $premiumResident->syncRoles(['Resident']);
        }
    }
}
