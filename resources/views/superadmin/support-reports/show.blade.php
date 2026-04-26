@extends('superadmin.layouts.app')

@section('title', 'Ticket '.$ticket->ticket_no)

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Ticket {{ $ticket->ticket_no }}</h1>
        <p class="mt-1 text-sm text-slate-500">Full report details submitted by tenant users.</p>
    </div>
    <a href="{{ route('super-admin.support-reports.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Back to Tenants reports
    </a>
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 lg:col-span-2">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Issue details</h2>
        <div class="mt-3 space-y-3">
            <div>
                <div class="text-xs font-medium uppercase text-slate-500">Subject</div>
                <div class="text-sm text-slate-800">{{ $ticket->subject }}</div>
            </div>
            <div>
                <div class="text-xs font-medium uppercase text-slate-500">Description</div>
                <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-700">{{ $ticket->description }}</div>
            </div>
            @if($ticket->attachment_url)
                <div>
                    <div class="text-xs font-medium uppercase text-slate-500">Attachment</div>
                    <a href="{{ route('super-admin.support-reports.attachment', $ticket) }}" target="_blank" rel="noopener" class="mt-1 inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        View attached screenshot
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Reporter info</h2>
        <div class="mt-3 space-y-2 text-sm text-slate-700">
            <div><span class="font-medium text-slate-900">Barangay:</span> {{ $ticket->tenant?->barangayDisplayName() ?? ('Tenant #'.$ticket->tenant_id) }}</div>
            <div><span class="font-medium text-slate-900">Name:</span> {{ $ticket->resolved_reporter_name ?: ($ticket->reporter_name ?: ($ticket->creator?->name ?? 'Unknown')) }}</div>
            @if($ticket->resolved_reporter_email || $ticket->reporter_email || $ticket->creator?->email)
                <div><span class="font-medium text-slate-900">Email:</span> {{ $ticket->resolved_reporter_email ?: ($ticket->reporter_email ?: $ticket->creator?->email) }}</div>
            @endif
            <div><span class="font-medium text-slate-900">Category:</span> {{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</div>
            <div><span class="font-medium text-slate-900">Priority:</span> {{ ucfirst($ticket->priority) }}</div>
            <div><span class="font-medium text-slate-900">Status:</span> {{ \App\Models\SupportTicket::statusLabel($ticket->status) }}</div>
            <div><span class="font-medium text-slate-900">Created:</span> {{ \App\Support\DateDisplay::format($ticket->created_at, 'M d, Y h:i:s A') }}</div>
        </div>
        <form method="POST" action="{{ route('super-admin.support-reports.status', $ticket) }}" class="mt-4 space-y-2">
            @csrf
            @method('PATCH')
            <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Update status</label>
            <select name="status" id="status" class="w-full rounded-xl border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                @foreach(['pending', 'fixing', 'done'] as $statusKey)
                    <option value="{{ $statusKey }}" {{ \App\Models\SupportTicket::superAdminKeyFromStatus((string) $ticket->status) === $statusKey ? 'selected' : '' }}>
                        {{ ucfirst($statusKey) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="w-full rounded-xl bg-violet-700 px-4 py-2 text-sm font-medium text-white hover:bg-violet-800">Save status</button>
        </form>
    </div>
</div>

<div class="mt-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Message thread</h2>
    <div class="mt-3 space-y-3">
        @forelse($ticket->messages as $message)
            <div class="rounded-xl border border-slate-200 p-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-sm font-medium text-slate-800">{{ $message->resolved_author_name ?: ($message->author_name ?: ($message->author?->name ?? 'Unknown user')) }}</div>
                    <div class="text-xs text-slate-500">{{ \App\Support\DateDisplay::format($message->created_at, 'M d, Y h:i:s A') }}</div>
                </div>
                @if($message->resolved_author_email || $message->author_email || $message->author?->email)
                    <div class="mt-0.5 text-xs text-slate-500">{{ $message->resolved_author_email ?: ($message->author_email ?: $message->author?->email) }}</div>
                @endif
                <div class="mt-2 text-sm text-slate-700">{{ $message->message }}</div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No replies yet.</p>
        @endforelse
    </div>
</div>
@endsection
