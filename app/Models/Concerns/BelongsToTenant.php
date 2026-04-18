<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    /**
     * Boot the trait. Add the TenantScope so all queries are scoped by tenant.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
