<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Service;
use Database\Seeders\RoleAndPermissionSeeder;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class SeedTenantInitialData
{
    public function __construct(private readonly TenantWithDatabase $tenant) {}

    /**
     * Seed only tenant-specific initial data (roles/permissions + rbac rows + default services).
     * This avoids running your full DatabaseSeeder, which seeds across all tenants.
     */
    public function handle(): void
    {
        // Run seeding in the tenant's DB context.
        tenancy()->initialize($this->tenant);

        try {
            (new RoleAndPermissionSeeder)->run();

            // Create per-tenant RBAC rows (tenant_role_permissions).
            TenantRbacSeeder::seedTenant((int) $this->tenant->getTenantKey());

            // Seed default services only for this tenant.
            $defaultServices = [
                ['name' => 'Consultation', 'duration_minutes' => 15, 'sort_order' => 1],
                ['name' => 'Prenatal Check-up', 'duration_minutes' => 30, 'sort_order' => 2],
                ['name' => 'Immunization', 'duration_minutes' => 15, 'sort_order' => 3],
                ['name' => 'Blood Pressure Check', 'duration_minutes' => 10, 'sort_order' => 4],
                ['name' => 'Dental', 'duration_minutes' => 20, 'sort_order' => 5],
            ];

            foreach ($defaultServices as $svc) {
                Service::firstOrCreate(
                    ['tenant_id' => $this->tenant->getTenantKey(), 'name' => $svc['name']],
                    [
                        'duration_minutes' => $svc['duration_minutes'],
                        'sort_order' => $svc['sort_order'],
                        'is_active' => true,
                    ]
                );
            }
        } finally {
            tenancy()->end();
        }
    }
}
