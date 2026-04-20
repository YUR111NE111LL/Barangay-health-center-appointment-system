<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    private function routeBase(): string
    {
        return request()->routeIs('resident.*') ? 'resident.support' : 'backend.support';
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        $isAdmin = $user->hasTenantBarangayAdministrationAccess();

        $tickets = SupportTicket::query()
            ->where('tenant_id', $tenant->id)
            ->when(! $isAdmin, function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('status', (string) $request->query('status'));
            })
            ->when($request->filled('priority'), function ($query) use ($request): void {
                $query->where('priority', (string) $request->query('priority'));
            })
            ->with(['creator:id,name,email', 'assignee:id,name'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $routeBase = $this->routeBase();

        return view('tenant.support.tickets.index', compact('tickets', 'isAdmin', 'routeBase'));
    }

    public function create(): View
    {
        $routeBase = $this->routeBase();

        return view('tenant.support.tickets.create', compact('routeBase'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $validated = $request->validate([
            'category' => ['required', 'in:bug,account,feature_request,general'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'min:10'],
            'attachment' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'webp'])->max(4096)],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support-tickets/'.$tenant->id, 'public');
        }

        $centralConnection = (string) config('tenancy.database.central_connection', 'central');

        try {
            DB::connection($centralConnection)->transaction(function () use ($user, $tenant, $validated, $attachmentPath): void {
                $year = (int) now()->format('Y');
                $nextSeq = (int) SupportTicket::query()
                    ->whereYear('created_at', $year)
                    ->lockForUpdate()
                    ->count() + 1;
                $ticketNo = sprintf('SUP-%d-%04d', $year, $nextSeq);

                SupportTicket::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'reporter_name' => $user->name,
                    'reporter_email' => $user->email,
                    'ticket_no' => $ticketNo,
                    'category' => $validated['category'],
                    'priority' => $validated['priority'],
                    'subject' => $validated['subject'],
                    'description' => $validated['description'],
                    'attachment_path' => $attachmentPath,
                    'status' => 'open',
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', __('We could not save your ticket. Please try again in a moment.'));
        }

        return redirect()->route($this->routeBase().'.tickets.index')->with('success', 'Support ticket created successfully.');
    }

    public function show(SupportTicket $ticket): View
    {
        $this->authorizeTicket($ticket);
        $user = Auth::user();
        $isAdmin = $user->hasTenantBarangayAdministrationAccess();

        $ticket->load([
            'creator:id,name,email',
            'assignee:id,name',
            'messages' => fn ($query) => $query->with('author:id,name,role')->oldest(),
        ]);

        $routeBase = $this->routeBase();

        return view('tenant.support.tickets.show', compact('ticket', 'isAdmin', 'routeBase'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($ticket);
        $user = Auth::user();
        $isAdmin = $user->hasTenantBarangayAdministrationAccess();

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2'],
            'is_internal_note' => ['nullable', 'boolean'],
        ]);

        $isInternal = $isAdmin ? (bool) ($validated['is_internal_note'] ?? false) : false;

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'tenant_id' => $ticket->tenant_id,
            'user_id' => $user->id,
            'author_name' => $user->name,
            'author_email' => $user->email,
            'message' => $validated['message'],
            'is_internal_note' => $isInternal,
        ]);

        if ($ticket->status === 'open' && $isAdmin) {
            $ticket->update(['status' => 'in_progress']);
        }

        return redirect()->route($this->routeBase().'.tickets.show', $ticket)->with('success', 'Reply posted.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($ticket);
        $user = Auth::user();
        $isAdmin = $user->hasTenantBarangayAdministrationAccess();

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $nextStatus = (string) $validated['status'];
        $allowedForUser = $isAdmin ? ['open', 'in_progress', 'resolved', 'closed'] : ['open', 'closed'];
        if (! in_array($nextStatus, $allowedForUser, true)) {
            return back()->with('error', 'You cannot set that status.');
        }

        $ticket->status = $nextStatus;
        $ticket->resolved_at = in_array($nextStatus, ['resolved', 'closed'], true) ? now() : null;
        $ticket->save();

        return redirect()->route($this->routeBase().'.tickets.show', $ticket)->with('success', 'Ticket status updated.');
    }

    public function destroy(SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($ticket);

        $ticket->delete();

        return redirect()->route($this->routeBase().'.tickets.index')->with('success', 'Support ticket deleted.');
    }

    private function authorizeTicket(SupportTicket $ticket): void
    {
        $user = Auth::user();
        if ((int) $ticket->tenant_id !== (int) $user->tenant_id) {
            abort(403);
        }

        $isAdmin = $user->hasTenantBarangayAdministrationAccess();
        if (! $isAdmin && (int) $ticket->user_id !== (int) $user->id) {
            abort(403);
        }
    }
}
