@extends('tenant.layouts.app')

@section('title', 'Support Tickets')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Support Tickets</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $isAdmin ? 'View and handle all barangay tickets.' : 'Track your submitted support requests.' }}</p>
    </div>
    <a href="{{ route($routeBase . '.tickets.create') }}" class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-teal-700">Create ticket</a>
</div>

<form method="GET" class="mb-4 grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60 sm:grid-cols-3">
    <select name="status" class="rounded-xl border-slate-300 bg-slate-50 px-3 py-2 text-sm">
        <option value="">All statuses</option>
        @foreach(['open', 'in_progress', 'resolved', 'closed'] as $status)
            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ \App\Models\SupportTicket::statusLabel($status) }}</option>
        @endforeach
    </select>
    <select name="priority" class="rounded-xl border-slate-300 bg-slate-50 px-3 py-2 text-sm">
        <option value="">All priorities</option>
        @foreach(['low', 'medium', 'high', 'urgent'] as $priority)
            <option value="{{ $priority }}" {{ request('priority') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
        @endforeach
    </select>
    <div class="flex gap-2">
        <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
        <a href="{{ route($routeBase . '.tickets.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</a>
    </div>
</form>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Ticket</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($tickets as $ticket)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $ticket->ticket_no }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">
                        {{ $ticket->subject }}
                        <div class="text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }} · by {{ $ticket->creator?->name }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ ucfirst($ticket->priority) }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ \App\Models\SupportTicket::statusLabel($ticket->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-3">
                            <a href="{{ route($routeBase . '.tickets.show', $ticket) }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Open</a>
                            <form action="{{ route($routeBase . '.tickets.destroy', $ticket) }}" method="POST" onsubmit="return confirm('Delete this support ticket? This cannot be undone.');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-700">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No tickets yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $tickets->links() }}</div>
@endsection
