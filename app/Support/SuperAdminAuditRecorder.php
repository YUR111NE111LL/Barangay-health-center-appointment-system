<?php

namespace App\Support;

use App\Models\SuperAdminAuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminAuditRecorder
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
        '_token',
        'g-recaptcha-response',
        'recaptcha_token',
    ];

    public static function recordAuth(string $event, User $user, ?array $newValues): void
    {
        if (! $user->isSuperAdmin() || ! self::auditTableExists()) {
            return;
        }

        SuperAdminAuditLog::query()->create([
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

    public static function recordTenantCreated(Tenant $tenant): void
    {
        if (! self::auditTableExists()) {
            return;
        }

        $actor = Auth::user();
        $actorId = $actor instanceof User ? $actor->id : null;
        $actorRole = $actor instanceof User ? $actor->role : null;

        SuperAdminAuditLog::query()->create([
            'user_id' => $actorId,
            'user_role' => $actorRole,
            'ip_address' => request()?->ip(),
            'event' => 'created',
            'auditable_type' => Tenant::class,
            'auditable_id' => (int) $tenant->getKey(),
            'old_values' => null,
            'new_values' => [
                'name' => $tenant->name,
                'email' => $tenant->email,
                'plan_id' => $tenant->plan_id,
                'is_active' => (bool) $tenant->is_active,
            ],
            'created_at' => now(),
        ]);
    }

    public static function recordAction(User $user, Request $request, Response $response): void
    {
        if (! $user->isSuperAdmin() || ! self::auditTableExists()) {
            return;
        }

        $route = $request->route();
        $routeName = is_object($route) ? (string) ($route->getName() ?? '') : '';
        $event = self::eventFromMethod($request->method());

        SuperAdminAuditLog::query()->create([
            'user_id' => $user->id,
            'user_role' => $user->role,
            'ip_address' => $request->ip(),
            'event' => $event,
            'auditable_type' => 'route',
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => [
                'method' => strtoupper($request->method()),
                'route_name' => $routeName !== '' ? $routeName : null,
                'path' => '/'.ltrim($request->path(), '/'),
                'status' => $response->getStatusCode(),
                'query' => self::sanitize($request->query()),
                'payload' => self::sanitize($request->except(array_merge(self::SENSITIVE_KEYS, ['session_portal']))),
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function sanitize(array $values): array
    {
        foreach (self::SENSITIVE_KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[redacted]';
            }
        }

        return $values;
    }

    private static function eventFromMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'GET', 'HEAD' => 'viewed',
            'POST' => 'action',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => 'action',
        };
    }

    private static function auditTableExists(): bool
    {
        return Schema::connection(config('database.default'))->hasTable('super_admin_audit_logs');
    }
}
