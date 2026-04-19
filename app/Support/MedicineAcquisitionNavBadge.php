<?php

namespace App\Support;

use App\Models\MedicineAcquisition;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Per-staff "unseen" medicine acquisition count (nav badge), using {@see User::$medicine_acquisitions_last_ack_id}.
 */
final class MedicineAcquisitionNavBadge
{
    public static function unseenCount(?User $user): int
    {
        if (! $user || ! $user->tenant_id) {
            return 0;
        }

        if (! Schema::hasTable('medicine_acquisitions') || ! Schema::hasColumn('users', 'medicine_acquisitions_last_ack_id')) {
            return 0;
        }

        if (! $user->hasTenantPermission('manage medicine') && ! $user->hasTenantPermission('manage inventory')) {
            return 0;
        }

        $tenant = $user->tenant;
        if (! $tenant || ! $tenant->hasFeature('inventory')) {
            return 0;
        }

        $lastAck = (int) ($user->medicine_acquisitions_last_ack_id ?? 0);

        return MedicineAcquisition::query()->where('id', '>', $lastAck)->count();
    }

    public static function acknowledgeFor(User $user): void
    {
        if (! Schema::hasTable('medicine_acquisitions') || ! Schema::hasColumn('users', 'medicine_acquisitions_last_ack_id')) {
            return;
        }

        $max = MedicineAcquisition::query()->max('id');
        if ($max === null) {
            return;
        }

        $user->forceFill(['medicine_acquisitions_last_ack_id' => (int) $max])->save();
    }
}
