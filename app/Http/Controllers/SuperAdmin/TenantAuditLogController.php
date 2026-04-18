<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;

class TenantAuditLogController extends Controller
{
    /**
     * Super Admin: choose a barangay to open its tenant audit log.
     */
    public function directory(): View
    {
        $tenants = Tenant::query()
            ->with(['domains', 'plan'])
            ->orderBy('name')
            ->paginate(15);

        return view('superadmin.tenant-audit-logs.index', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Super Admin: read-only audit log for a tenant (data lives in the tenant database).
     */
    public function index(Tenant $tenant): View|RedirectResponse
    {
        try {
            return $tenant->run(function () use ($tenant): View {
                /** @var string $connection */
                $connection = config('database.default');

                if (! Schema::connection($connection)->hasTable('audit_logs')) {
                    $logs = new LengthAwarePaginator(
                        [],
                        0,
                        10,
                        Paginator::resolveCurrentPage(),
                        ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
                    );

                    return view('superadmin.tenants.audit-log', [
                        'tenant' => $tenant,
                        'logs' => $logs,
                        'auditLogTableMissing' => true,
                    ]);
                }

                $logs = AuditLog::on($connection)
                    ->with('user:id,name,email,role')
                    ->latest('created_at')
                    ->paginate(10);

                return view('superadmin.tenants.audit-log', [
                    'tenant' => $tenant,
                    'logs' => $logs,
                    'auditLogTableMissing' => false,
                ]);
            });
        } catch (TenantDatabaseDoesNotExistException $e) {
            return redirect()
                ->route('super-admin.tenants.show', $tenant)
                ->with('error', __('This tenant database is not provisioned yet. Use “Provision Tenant DB” on the tenant page.'));
        }
    }
}
