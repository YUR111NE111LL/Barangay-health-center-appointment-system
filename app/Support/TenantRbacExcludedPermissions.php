<?php

namespace App\Support;

/**
 * Central-platform permission names that must never appear in tenant RBAC UIs
 * (Barangay Admin or Super Admin per-tenant RBAC), even when the plan allows '*'.
 */
final class TenantRbacExcludedPermissions
{
    /**
     * @return list<string>
     */
    public static function names(): array
    {
        $raw = config('bhcas.tenant_rbac_excluded_permissions', []);

        return array_values(array_filter(is_array($raw) ? $raw : []));
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    public static function filterList(array $names): array
    {
        $excluded = self::names();
        if ($excluded === []) {
            return array_values($names);
        }

        return array_values(array_diff($names, $excluded));
    }
}
