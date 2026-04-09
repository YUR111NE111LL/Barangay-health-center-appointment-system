@extends('tenant.layouts.app')

@section('title', 'Release Notes')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Release Notes</h1>
        <p class="mt-1 text-sm text-slate-500">Stay informed with latest features, fixes, and maintenance updates.</p>
    </div>
    @if($isAdmin && str_starts_with($routeBase, 'backend.'))
        <a href="{{ route($routeBase . '.updates.create') }}" class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-teal-700">Publish update</a>
    @endif
</div>

<div class="space-y-4">
    @forelse($notes as $note)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold text-slate-800">{{ $note->title }}</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ strtoupper($note->type) }}</span>
                @if($note->version)
                    <span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-700">v{{ $note->version }}</span>
                @endif
                @if($note->is_pinned)
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Pinned</span>
                @endif
            </div>
            @if($note->summary)
                <p class="mt-2 text-sm text-slate-600">{{ $note->summary }}</p>
            @endif
            @if($note->content)
                <div class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $note->content }}</div>
            @endif
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>Published {{ optional($note->published_at)->format('M d, Y h:i A') }}</span>
                @if($note->creator)
                    <span>By {{ $note->creator->name }}</span>
                @endif
            </div>
            @if($isAdmin && str_starts_with($routeBase, 'backend.') && (int) $note->tenant_id === (int) auth()->user()->tenant_id)
                <div class="mt-3 flex gap-2">
                    <a href="{{ route($routeBase . '.updates.edit', $note) }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Edit</a>
                    <form action="{{ route($routeBase . '.updates.destroy', $note) }}" method="POST" onsubmit="return confirm('Delete this update note?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-700">Delete</button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-2xl bg-white p-6 text-sm text-slate-500 shadow-sm ring-1 ring-slate-200/60">No release notes yet.</div>
    @endforelse
</div>

<div class="mt-4">{{ $notes->links() }}</div>
@endsection
