<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()?->role === User::ROLE_HEALTH_CENTER_ADMIN, 403);

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

        $this->appendApprovedByDisplay($logs, $connection);

        return view('tenant.audit-log.index', [
            'logs' => $logs,
            'auditLogTableMissing' => false,
        ]);
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

        $approvers = User::on($connection)
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
