@extends('superadmin.layouts.app')

@section('title', 'Tenants Reports')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Tenants Reports</h1>
    <p class="mt-1 text-sm text-slate-500">Tenant-submitted support tickets across all barangays.</p>
</div>

<form method="GET" class="mb-4 grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60 sm:grid-cols-3">
    <select name="status" class="rounded-xl border-slate-300 bg-slate-50 px-3 py-2 text-sm">
        <option value="">All statuses</option>
        @foreach(['pending', 'fixing', 'done'] as $status)
            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <select name="priority" class="rounded-xl border-slate-300 bg-slate-50 px-3 py-2 text-sm">
        <option value="">All priorities</option>
        @foreach(['low', 'medium', 'high', 'urgent'] as $priority)
            <option value="{{ $priority }}" {{ request('priority') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
        @endforeach
    </select>
    <div class="flex gap-2">
        <button type="submit" class="rounded-xl bg-violet-700 px-4 py-2 text-sm font-medium text-white hover:bg-violet-800">Filter</button>
        <a href="{{ route('super-admin.support-reports.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</a>
    </div>
</form>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Ticket</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Barangay</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Reported by</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Created</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-slate-50/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-800">{{ $ticket->ticket_no }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $ticket->tenant?->barangayDisplayName() ?? ('Tenant #'.$ticket->tenant_id) }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $ticket->subject }}</td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ \App\Models\SupportTicket::statusLabel($ticket->status) }}</span></td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ ucfirst($ticket->priority) }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            <div class="font-medium text-slate-800">{{ $ticket->resolved_reporter_name ?: ($ticket->reporter_name ?: ('Unknown (user #'.$ticket->user_id.')')) }}</div>
                            @if($ticket->resolved_reporter_email || $ticket->reporter_email)
                                <div class="text-xs text-slate-500">{{ $ticket->resolved_reporter_email ?: $ticket->reporter_email }}</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{{ $ticket->created_at?->format('M d, Y h:i A') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <a href="{{ route('super-admin.support-reports.show', $ticket) }}" class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">No support reports found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $tickets->links() }}</div>
@endsection
