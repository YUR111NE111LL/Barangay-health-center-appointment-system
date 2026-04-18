<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->role === User::ROLE_HEALTH_CENTER_ADMIN, 403);

        /** @var string|null $connection Explicit tenant connection so we never read the central DB by mistake. */
        $connection = config('database.default');

        if (! Schema::connection($connection)->hasTable('audit_logs')) {
            $logs = new LengthAwarePaginator(
                [],
                0,
                10,
                Paginator::resolveCurrentPage(),
                ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
            );

            return view('tenant.audit-log.index', [
                'logs' => $logs,
                'auditLogTableMissing' => true,
            ]);
        }

        $logs = AuditLog::on($connection)
            ->with('user:id,name,email,role')
            ->latest('created_at')
            ->paginate(10);

        return view('tenant.audit-log.index', [
            'logs' => $logs,
            'auditLogTableMissing' => false,
        ]);
    }
}
