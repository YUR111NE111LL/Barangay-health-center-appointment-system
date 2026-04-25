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

                $this->appendApprovedByDisplay($logs, $connection);

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

    private function appendApprovedByDisplay(LengthAwarePaginator $logs, string $connection): void
    {
        $approverIds = collect($logs->items())
            ->flatMap(function (AuditLog $log): array {
                $ids = [];
                foreach ([$log->old_values, $log->new_values] as $values) {
                    if (is_array($values) && isset($values['approved_by']) && is_numeric($values['approved_by'])) {
                        $ids[] = (int) $values['approved_by'];
                    }
                }

                return $ids;
            })
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($approverIds->isEmpty()) {
            return;
        }

        $approvers = \App\Models\User::on($connection)
            ->select(['id', 'name', 'role'])
            ->whereIn('id', $approverIds->all())
            ->get()
            ->keyBy('id');

        $logs->setCollection(
            $logs->getCollection()->map(function (AuditLog $log) use ($approvers): AuditLog {
                $log->old_values = $this->decorateApprovedBy($log->old_values, $approvers);
                $log->new_values = $this->decorateApprovedBy($log->new_values, $approvers);

                return $log;
            })
        );
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @param  \Illuminate\Support\Collection<int, \App\Models\User>  $approvers
     * @return array<string, mixed>|null
     */
    private function decorateApprovedBy(?array $values, \Illuminate\Support\Collection $approvers): ?array
    {
        if (! is_array($values) || ! isset($values['approved_by']) || ! is_numeric($values['approved_by'])) {
            return $values;
        }

        $approverId = (int) $values['approved_by'];
        if ($approverId <= 0) {
            return $values;
        }

        $approver = $approvers->get($approverId);
        if (! $approver) {
            return $values;
        }

        $values['approved_by_user'] = [
            'id' => $approver->id,
            'name' => $approver->name,
            'role' => $approver->role,
        ];

        return $values;
    }
}
