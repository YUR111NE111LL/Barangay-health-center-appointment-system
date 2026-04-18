<?php

namespace App\Support;

use App\Services\TenantRbacSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves permission names per role for a tenant from tenant_role_permissions
 * (same data {@see \App\Models\User::hasTenantPermission()} uses).
 */
final class TenantRoleEffectivePermissions
{
    /**
     * All permission names per role for one tenant, keyed by normalized role name
     * (mb_strtolower(trim(role_name))) for lookup alongside {@see User::$role}.
     *
     * @return array<string, list<string>>
     */
    public static function groupedByRoleKey(int $tenantId): array
    {
        if (! Schema::hasTable('tenant_role_permissions')) {
            return [];
        }

        TenantRbacSeeder::seedTenant($tenantId);

        $rows = DB::table('tenant_role_permissions')
            ->where('tenant_id', $tenantId)
            ->orderBy('permission_name')
            ->get(['role_name', 'permission_name']);

        $grouped = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) $row->role_name));
            if ($key === '') {
                continue;
            }
            if (! isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $row->permission_name;
        }

        foreach ($grouped as $key => $names) {
            $grouped[$key] = array_values(array_unique(TenantRbacExcludedPermissions::filterList($names)));
            sort($grouped[$key]);
        }

        return $grouped;
    }

    /**
     * @param  array<string, list<string>>  $grouped
     * @return list<string>
     */
    public static function forRoleName(array $grouped, ?string $roleName): array
    {
        $key = mb_strtolower(trim((string) $roleName));
        if ($key === '') {
            return [];
        }

        return $grouped[$key] ?? [];
    }
}
