<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();
        $defaultServices = [
            ['name' => 'Consultation', 'duration_minutes' => 15, 'sort_order' => 1],
            ['name' => 'Prenatal Check-up', 'duration_minutes' => 30, 'sort_order' => 2],
            ['name' => 'Immunization', 'duration_minutes' => 15, 'sort_order' => 3],
            ['name' => 'Blood Pressure Check', 'duration_minutes' => 10, 'sort_order' => 4],
            ['name' => 'Dental', 'duration_minutes' => 20, 'sort_order' => 5],
        ];

        foreach ($tenants as $tenant) {
            foreach ($defaultServices as $svc) {
                Service::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $svc['name']],
                    [
                        'duration_minutes' => $svc['duration_minutes'],
                        'sort_order' => $svc['sort_order'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
