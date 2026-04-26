<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminAuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

class SuperAdminAuditLogController extends Controller
{
    public function index(): View
    {
        if (! Schema::connection(config('database.default'))->hasTable('super_admin_audit_logs')) {
            $logs = new LengthAwarePaginator(
                [],
                0,
                10,
                Paginator::resolveCurrentPage(),
                ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
            );

            return view('superadmin.audit-log.index', [
                'logs' => $logs,
                'auditLogTableMissing' => true,
            ]);
        }

        $logs = SuperAdminAuditLog::query()
            ->with('user:id,name,email,role')
            ->latest('created_at')
            ->paginate(10);

        return view('superadmin.audit-log.index', [
            'logs' => $logs,
            'auditLogTableMissing' => false,
        ]);
    }
}
