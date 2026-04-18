<?php

namespace App\Models\Concerns;

/**
 * Tenant-scoped models are stored in each tenant database. Without this, a
 * {@see \Illuminate\Database\Eloquent\Relations\HasMany} from {@see \App\Models\Tenant}
 * would copy the parent's central connection and query the wrong database.
 */
trait UsesTenantDatabaseConnection
{
    public function getConnectionName(): ?string
    {
        if (function_exists('tenant') && tenant()) {
            return config('database.default');
        }

        return null;
    }
}
