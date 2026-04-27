<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            TenantSeeder::class,
            UserSeeder::class,
            ServiceSeeder::class,
            EventSeeder::class,
            ModuleSeeder::class,
        ]);
    }

}
