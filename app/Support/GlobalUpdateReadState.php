<?php

namespace App\Support;

use App\Models\ReleaseNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks per-user "seen" state for global (Super Admin) release notes.
 * Uses DB column when present; otherwise falls back to session so dismiss works without tenant migrations.
 */
class GlobalUpdateReadState
{
    private const SESSION_KEY_PREFIX = 'global_updates_last_seen.';

    public static function sessionKey(User $user): string
    {
        return self::SESSION_KEY_PREFIX.$user->id;
    }

    public static function usersTableHasSeenColumn(User $user): bool
    {
        $connection = $user->getConnectionName();

        return Schema::connection($connection)->hasColumn($user->getTable(), 'last_seen_global_update_at');
    }

    /**
     * When the user last acknowledged global updates (DB or session).
     */
    public static function lastSeenAt(?User $user): ?Carbon
    {
        if (! $user || ! $user->tenant_id) {
            return null;
        }

        if (self::usersTableHasSeenColumn($user)) {
            $fresh = User::query()->find($user->id);

            return $fresh?->last_seen_global_update_at;
        }

        $raw = session(self::sessionKey($user));
        if (! $raw || ! is_string($raw)) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Persist "seen" so global update badges clear for this user only.
     */
    public static function markSeen(User $user): void
    {
        if (! $user->tenant_id) {
            return;
        }

        $now = now();

        if (self::usersTableHasSeenColumn($user)) {
            User::query()->whereKey($user->id)->update(['last_seen_global_update_at' => $now]);

            return;
        }

        session([self::sessionKey($user) => $now->toIso8601String()]);
    }

    /**
     * Count of global release notes this user has not yet acknowledged.
     */
    public static function unreadGlobalCount(User $user): int
    {
        if (! $user->tenant_id) {
            return 0;
        }

        $lastSeen = self::lastSeenAt($user);

        return ReleaseNote::query()
            ->whereNull('tenant_id')
            ->whereNotNull('published_at')
            ->when($lastSeen, function ($query) use ($lastSeen): void {
                $query->where('published_at', '>', $lastSeen);
            })
            ->count();
    }
}
