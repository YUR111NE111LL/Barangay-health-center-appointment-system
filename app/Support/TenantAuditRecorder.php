<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Persists audit rows only when a tenant context is active (tenant database connection).
 */
final class TenantAuditRecorder
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = ['password', 'remember_token'];

    public static function record(string $event, Model $model, ?array $oldValues = null, ?array $newValues = null): void
    {
        if (! tenant()) {
            return;
        }

        if ($model instanceof AuditLog) {
            return;
        }

        if (! self::auditLogsTableExists()) {
            return;
        }

        $actor = Auth::user();
        $actorId = $actor instanceof User ? $actor->id : null;
        $actorRole = $actor instanceof User ? $actor->role : null;

        AuditLog::on(config('database.default'))->create([
            'user_id' => $actorId,
            'user_role' => $actorRole,
            'ip_address' => request()?->ip(),
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues !== null ? self::sanitize($oldValues) : null,
            'new_values' => $newValues !== null ? self::sanitize($newValues) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function sanitize(array $data): array
    {
        foreach (self::SENSITIVE_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '[redacted]';
            }
        }

        return $data;
    }

    /**
     * Record sign-in / sign-out for tenant users. Does not run for Super Admin (no tenant_id).
     * The audit log page itself remains restricted to Health Center Admin via routes and middleware.
     *
     * @param  array<string, mixed>|null  $newValues
     */
    public static function recordAuth(string $event, User $user, ?array $newValues): void
    {
        if (! tenant()) {
            return;
        }

        if (! self::auditLogsTableExists()) {
            return;
        }

        if ($user->tenant_id === null) {
            return;
        }

        AuditLog::on(config('database.default'))->create([
            'user_id' => $user->id,
            'user_role' => $user->role,
            'ip_address' => request()?->ip(),
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }

    private static function auditLogsTableExists(): bool
    {
        return Schema::connection(config('database.default'))->hasTable('audit_logs');
    }
}
