<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Creates a tenant record, primary domain, and tenant database (same behavior as Super Admin "Add tenant").
 *
 * @phpstan-type ValidatedTenantData array{
 *     plan_id: int,
 *     name: string,
 *     domain: string,
 *     address?: string|null,
 *     contact_number?: string|null,
 *     email?: string|null,
 *     is_active: bool,
 *     subscription_ends_at?: string|null,
 * }
 */
class TenantCreationService
{
    /**
     * Full flow: tenant row + domain + tenant database (no outer transaction required).
     *
     * @param  ValidatedTenantData  $validated
     */
    public function createFromValidatedData(array $validated, string $barangaySlugSource): Tenant
    {
        $tenant = $this->createTenantRecordAndDomain($validated, $barangaySlugSource);
        $this->provisionTenantDatabaseIfNeeded($tenant);

        return $tenant;
    }

    /**
     * Persist tenant + primary domain only. Safe to run inside {@see \Illuminate\Support\Facades\DB::transaction()}.
     * Does not run DDL (CREATE DATABASE) — MySQL implicitly commits open transactions on DDL, which breaks Laravel transactions.
     *
     * @param  ValidatedTenantData  $validated
     */
    public function createTenantRecordAndDomain(array $validated, string $barangaySlugSource): Tenant
    {
        Plan::findOrFail($validated['plan_id']);
        $dbName = $this->buildHashedTenantDatabaseName();

        $tenant = Tenant::create([
            'plan_id' => $validated['plan_id'],
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $validated['is_active'],
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
            'data' => [
                'tenancy_db_name' => $dbName,
            ],
        ]);

        $tenant->domains()->create([
            'domain' => Str::lower($validated['domain']),
        ]);

        return $tenant;
    }

    /**
     * Create the tenant MySQL database if missing (DDL — run outside DB::transaction).
     */
    public function provisionTenantDatabaseIfNeeded(Tenant $tenant): void
    {
        $tenantDatabaseName = $tenant->database()->getName();
        $tenantDatabaseManager = $tenant->database()->manager();
        if (! $tenantDatabaseManager->databaseExists($tenantDatabaseName)) {
            $tenant->database()->makeCredentials();
            $tenantDatabaseManager->createDatabase($tenant);
        }
    }

    private function sanitizeTenantDatabaseName(string $dbName): string
    {
        $dbName = trim($dbName);
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $dbName) ?: '';

        if (strlen($dbName) > 64) {
            $dbName = substr($dbName, 0, 64);
        }

        if ($dbName !== '' && ! preg_match('/^[a-zA-Z]/', $dbName)) {
            $dbName = 'tenant_'.$dbName;
            if (strlen($dbName) > 64) {
                $dbName = substr($dbName, 0, 64);
            }
        }

        return $dbName;
    }

    /**
     * Build a tenant database name that keeps the public `tenant_` prefix
     * while hiding tenant-identifying metadata behind a random hash token.
     */
    private function buildHashedTenantDatabaseName(): string
    {
        $token = bin2hex(random_bytes(12));

        return $this->sanitizeTenantDatabaseName('tenant_'.$token);
    }
}
