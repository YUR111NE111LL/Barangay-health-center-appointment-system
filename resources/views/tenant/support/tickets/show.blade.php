@extends('tenant.layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_no)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ $ticket->ticket_no }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $ticket->subject }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route($routeBase . '.tickets.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to tickets</a>
        <form action="{{ route($routeBase . '.tickets.destroy', $ticket) }}" method="POST" onsubmit="return confirm('Delete this support ticket? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">
                Delete ticket
            </button>
        </form>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 lg:col-span-2">
        <h2 class="text-lg font-semibold text-slate-800">Conversation</h2>
        <div class="mt-4 space-y-3">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-sm text-slate-700">{{ $ticket->description }}</p>
                @if($ticket->attachment_url)
                    <div class="mt-3">
                        <a href="{{ $ticket->attachment_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg border border-teal-300 bg-teal-50 px-3 py-1.5 text-xs font-medium text-teal-700 hover:bg-teal-100">
                            View attached screenshot
                        </a>
                    </div>
                @endif
                <p class="mt-1 text-xs text-slate-500">Opened by {{ $ticket->creator?->name }} · {{ $ticket->created_at->format('M d, Y h:i A') }}</p>
            </div>

            @foreach($ticket->messages as $message)
                @if(!($message->is_internal_note && !$isAdmin))
                    <div class="rounded-lg border px-4 py-3 {{ $message->is_internal_note ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white' }}">
                        <p class="text-sm text-slate-700">{{ $message->message }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $message->author_name ?: ($message->author?->name ?? 'Unknown') }} · {{ $message->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                @endif
            @endforeach
        </div>

        <form action="{{ route($routeBase . '.tickets.reply', $ticket) }}" method="POST" class="mt-5 space-y-3">
            @csrf
            <div>
                <label for="message" class="mb-1 block text-sm font-medium text-slate-700">Reply</label>
                <textarea name="message" id="message" rows="4" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required></textarea>
            </div>
            @if($isAdmin)
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="is_internal_note" value="1" class="rounded border-slate-300 text-teal-600">
                    Internal note (visible to admins only)
                </label>
            @endif
            <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Post reply</button>
        </form>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="text-lg font-semibold text-slate-800">Ticket details</h2>
        <div class="mt-3 space-y-2 text-sm text-slate-700">
            <p><span class="font-medium">Category:</span> {{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</p>
            <p><span class="font-medium">Priority:</span> {{ ucfirst($ticket->priority) }}</p>
            <p><span class="font-medium">Status:</span> {{ \App\Models\SupportTicket::statusLabel($ticket->status) }}</p>
            <p><span class="font-medium">Requester:</span> {{ $ticket->creator?->name }}</p>
            <p><span class="font-medium">Assigned:</span> {{ $ticket->assignee?->name ?? 'Unassigned' }}</p>
        </div>

        <form action="{{ route($routeBase . '.tickets.status', $ticket) }}" method="POST" class="mt-4 space-y-2">
            @csrf
            @method('PATCH')
            <label for="status" class="block text-sm font-medium text-slate-700">Update status</label>
            <select name="status" id="status" class="w-full rounded-xl border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                @foreach(['open', 'in_progress', 'resolved', 'closed'] as $status)
                    <option value="{{ $status }}" {{ $ticket->status === $status ? 'selected' : '' }}>{{ \App\Models\SupportTicket::statusLabel($status) }}</option>
                @endforeach
            </select>
            <button type="submit" class="w-full rounded-xl bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Save status</button>
            @if(!$isAdmin)
                <p class="text-xs text-slate-500">You can only switch between Open and Closed.</p>
            @endif
        </form>
    </div>
</div>
@endsection
