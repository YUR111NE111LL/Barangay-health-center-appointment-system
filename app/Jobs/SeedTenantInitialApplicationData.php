<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SeedTenantInitialData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

/**
 * Runs after tenant migrations: Spatie roles/permissions, tenant_role_permissions, default services.
 * Tenant databases do not run {@see \Database\Seeders\DatabaseSeeder} automatically.
 */
class SeedTenantInitialApplicationData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected TenantWithDatabase $tenant) {}

    public function handle(): void
    {
        (new SeedTenantInitialData($this->tenant))->handle();
    }
}
