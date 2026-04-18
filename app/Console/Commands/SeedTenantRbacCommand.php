<?php

namespace App\Console\Commands;

use App\Services\TenantRbacSeeder;
use Illuminate\Console\Command;

class SeedTenantRbacCommand extends Command
{
    protected $signature = 'tenant:seed-rbac
                            {--all : Seed all tenants that have no RBAC rows (default)}
                            {--tenant= : Seed a specific tenant ID only}';

    protected $description = 'Seed tenant_role_permissions from global RBAC so tenant RBAC is enforced (run once after deploy or for existing tenants)';

    public function handle(): int
    {
        if ($this->option('tenant')) {
            $tenantId = (int) $this->option('tenant');
            TenantRbacSeeder::seedTenant($tenantId);
            $this->info("Seeded RBAC for tenant ID {$tenantId}.");

            return self::SUCCESS;
        }

        $count = TenantRbacSeeder::seedAllTenants();
        $this->info("Seeded RBAC for {$count} tenant(s). Tenants with existing RBAC were skipped.");

        return self::SUCCESS;
    }
}
