<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\TenantAuditRecorder;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cache;

class RecordTenantAuthAudit
{
    /** @var array<string, true> */
    private static array $recordedThisRequest = [];

    public function handleLogin(Login $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        if ($this->shouldSkipDuplicate('login', $user->id)) {
            return;
        }

        TenantAuditRecorder::recordAuth('login', $user, ['remember' => $event->remember]);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        if ($this->shouldSkipDuplicate('logout', $user->id)) {
            return;
        }

        TenantAuditRecorder::recordAuth('logout', $user, null);
    }

    private function shouldSkipDuplicate(string $kind, int $userId): bool
    {
        $slot = $kind.':'.$userId;
        if (isset(self::$recordedThisRequest[$slot])) {
            return true;
        }

        $tenantId = tenant()?->id;
        if ($tenantId !== null) {
            $cacheKey = 'tenant_audit_dedupe:'.$kind.':'.$tenantId.':'.$userId;
            if (! Cache::add($cacheKey, true, now()->addSeconds(3))) {
                return true;
            }
        }

        self::$recordedThisRequest[$slot] = true;

        return false;
    }
}
