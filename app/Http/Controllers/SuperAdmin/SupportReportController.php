<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Stancl\Tenancy\Tenancy;

class SupportReportController extends Controller
{
    /** @var array<string, array{name:?string,email:?string}> */
    private array $tenantUserIdentityCache = [];

    public function index(Request $request): View
    {
        $superAdminStatusChoices = SupportTicket::superAdminStatusChoices();
        $tickets = SupportTicket::query()
            ->when($request->filled('status'), function ($query) use ($request, $superAdminStatusChoices): void {
                $rawStatus = (string) $request->query('status');
                $mappedStatus = $superAdminStatusChoices[$rawStatus] ?? null;
                if ($mappedStatus) {
                    if ($rawStatus === 'done') {
                        $query->whereIn('status', [
                            SupportTicket::STATUS_RESOLVED,
                            SupportTicket::STATUS_CLOSED,
                        ]);
                    } else {
                        $query->where('status', $mappedStatus);
                    }
                }
            })
            ->when($request->filled('priority'), function ($query) use ($request): void {
                $query->where('priority', (string) $request->query('priority'));
            })
            ->with(['tenant:id,name,site_name', 'tenant.domains'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $tickets->getCollection()->transform(function (SupportTicket $ticket): SupportTicket {
            [$name, $email] = $this->reporterFromStoredColumnsOnly($ticket);
            $ticket->setAttribute('resolved_reporter_name', $name);
            $ticket->setAttribute('resolved_reporter_email', $email);

            return $ticket;
        });

        return view('superadmin.support-reports.index', compact('tickets'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load([
            'tenant:id,name,site_name',
            'tenant.domains',
            'creator:id,name,email',
            'messages' => function ($query): void {
                $query->latest('created_at');
            },
            'messages.author:id,name,email',
        ]);

        [$name, $email] = $this->resolveReporterIdentity($ticket);
        $ticket->setAttribute('resolved_reporter_name', $name);
        $ticket->setAttribute('resolved_reporter_email', $email);

        $ticket->messages->each(function ($message) use ($ticket): void {
            if (filled($message->author_name) || filled($message->author_email)) {
                $message->setAttribute('resolved_author_name', $message->author_name);
                $message->setAttribute('resolved_author_email', $message->author_email);

                return;
            }
            [$authorName, $authorEmail] = $this->resolveTenantUserIdentity((int) $ticket->tenant_id, (int) $message->user_id);
            $message->setAttribute('resolved_author_name', $authorName);
            $message->setAttribute('resolved_author_email', $authorEmail);
        });

        return view('superadmin.support-reports.show', compact('ticket'));
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,fixing,done'],
        ]);

        $mappedStatus = SupportTicket::superAdminStatusChoices()[$validated['status']] ?? SupportTicket::STATUS_OPEN;
        $statusWasChanged = $ticket->status !== $mappedStatus;

        $ticket->status = $mappedStatus;
        $ticket->resolved_at = $mappedStatus === SupportTicket::STATUS_RESOLVED ? now() : null;
        $ticket->save();

        if ($statusWasChanged) {
            /** @var User|null $superAdmin */
            $superAdmin = Auth::user();
            SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'tenant_id' => $ticket->tenant_id,
                'user_id' => $superAdmin?->id ?? $ticket->user_id,
                'author_name' => $superAdmin?->name ?? 'Super Admin',
                'author_email' => $superAdmin?->email,
                'message' => 'Status updated to '.SupportTicket::statusLabel($mappedStatus).' by Super Admin.',
                'is_internal_note' => false,
            ]);
        }

        return back()->with('success', 'Ticket status updated.');
    }

    /**
     * List view: never opens tenant DB connections (misconfigured/slow tenant DBs caused endless loads).
     *
     * @return array{0:?string,1:?string}
     */
    private function reporterFromStoredColumnsOnly(SupportTicket $ticket): array
    {
        if (filled($ticket->reporter_name) || filled($ticket->reporter_email)) {
            return [$ticket->reporter_name, $ticket->reporter_email];
        }

        return [null, null];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function resolveReporterIdentity(SupportTicket $ticket): array
    {
        if (filled($ticket->reporter_name) || filled($ticket->reporter_email)) {
            return [$ticket->reporter_name, $ticket->reporter_email];
        }

        return $this->resolveTenantUserIdentity((int) $ticket->tenant_id, (int) $ticket->user_id);
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function resolveTenantUserIdentity(int $tenantId, int $userId): array
    {
        if ($tenantId <= 0 || $userId <= 0) {
            return [null, null];
        }

        $cacheKey = $tenantId.':'.$userId;
        if (isset($this->tenantUserIdentityCache[$cacheKey])) {
            return [
                $this->tenantUserIdentityCache[$cacheKey]['name'],
                $this->tenantUserIdentityCache[$cacheKey]['email'],
            ];
        }

        $tenancy = app(Tenancy::class);
        $alreadyInitialized = $tenancy->initialized;
        $previousTenant = $tenancy->tenant;
        $resolvedName = null;
        $resolvedEmail = null;

        try {
            if (! $alreadyInitialized || (int) ($previousTenant?->id ?? 0) !== $tenantId) {
                $tenant = \App\Models\Tenant::query()->find($tenantId);
                if ($tenant) {
                    $tenancy->initialize($tenant);
                }
            }

            $tenantUser = User::query()->select(['id', 'name', 'email'])->find($userId);
            $resolvedName = $tenantUser?->name;
            $resolvedEmail = $tenantUser?->email;
        } catch (\Throwable) {
            $resolvedName = null;
            $resolvedEmail = null;
        } finally {
            $tenancy->end();
            if ($alreadyInitialized && $previousTenant) {
                $tenancy->initialize($previousTenant);
            }
        }

        $this->tenantUserIdentityCache[$cacheKey] = [
            'name' => $resolvedName,
            'email' => $resolvedEmail,
        ];

        return [$resolvedName, $resolvedEmail];
    }
}
