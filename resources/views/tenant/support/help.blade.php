@extends('tenant.layouts.app')

@section('title', 'Support & Updates')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Support &amp; Updates</h1>
        <p class="mt-1 text-sm text-slate-500">Get help, report issues, and see the latest system updates for your barangay.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route($routeBase . '.tickets.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Support tickets</a>
        <a href="{{ route($routeBase . '.updates.index') }}" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Release notes</a>
        @if(($supportUpdatesNotificationCount ?? 0) > 0)
            <form action="{{ route($routeBase . '.updates.clear-notification') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                    Clear update notification ({{ $supportUpdatesNotificationCount }})
                </button>
            </form>
        @endif
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="text-lg font-semibold text-slate-800">Contact support</h2>
        <p class="mt-2 text-sm text-slate-600">Need immediate help? Reach your support contact:</p>
        <div class="mt-4 space-y-1 text-sm text-slate-700">
            <p><span class="font-medium">Email:</span> {{ $supportContact['email'] }}</p>
            <p><span class="font-medium">Contact:</span> {{ $supportContact['contact'] }}</p>
            <p><span class="font-medium">Office hours:</span> {{ $supportContact['office_hours'] }}</p>
        </div>
        <a href="{{ route($routeBase . '.tickets.create') }}" class="mt-4 inline-flex rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700">Create ticket</a>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 lg:col-span-2">
        <h2 class="text-lg font-semibold text-slate-800">Frequently asked questions</h2>
        <div class="mt-4 space-y-3">
            @foreach($faqs as $faq)
                <details class="rounded-lg border border-slate-200 px-4 py-3">
                    <summary class="cursor-pointer text-sm font-medium text-slate-800">{{ $faq['q'] }}</summary>
                    <p class="mt-2 text-sm text-slate-600">{{ $faq['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
    <h2 class="text-lg font-semibold text-slate-800">Recent updates</h2>
    <div class="mt-4 space-y-3">
        @forelse($recentNotes as $note)
            <div class="rounded-lg border border-slate-200 px-4 py-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <h3 class="text-sm font-semibold text-slate-800">{{ $note->title }}</h3>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ strtoupper($note->type) }}</span>
                        @if($note->is_pinned)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Pinned</span>
                        @endif
                    </div>
                    @if(($isAdmin ?? false) && str_starts_with($routeBase, 'backend.') && $tenant && (int) $note->tenant_id === (int) $tenant->id)
                        <form action="{{ route($routeBase . '.updates.destroy', $note) }}" method="POST" class="shrink-0" onsubmit="return confirm('Delete this update note?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Delete</button>
                        </form>
                    @endif
                </div>
                @if($note->summary)
                    <p class="mt-1 text-sm text-slate-600">{{ $note->summary }}</p>
                @endif
                <p class="mt-1 text-xs text-slate-500">{{ optional($note->published_at)->format('M d, Y h:i A') }}</p>
            </div>
        @empty
            <p class="text-sm text-slate-500">No updates published yet.</p>
        @endforelse
    </div>
</div>
@endsection
