@extends('tenant.layouts.app')

@section('title', 'Release Notes')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Release Notes</h1>
        <p class="mt-1 text-sm text-slate-500">Stay informed with latest features, fixes, and maintenance updates.</p>
        <p class="mt-1 text-xs text-slate-500">Global updates published by Super Admin appear here automatically for tenant admins and users.</p>
    </div>
</div>

<div class="space-y-4">
    @forelse($notes as $note)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-slate-800">{{ $note->title }}</h2>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ strtoupper($note->type) }}</span>
                        @if($note->version)
                            <span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-700">v{{ $note->version }}</span>
                        @endif
                        @if($note->tenant_id === null)
                            <span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700">Global update</span>
                        @endif
                        @if($note->is_pinned)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Pinned</span>
                        @endif
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    @php
                        $isGitHubNote = is_string($note->external_ref ?? null) && str_starts_with((string) $note->external_ref, 'github:');
                        $downloadUrl = null;
                        if ($isGitHubNote && filled($note->version) && filled(config('github.owner')) && filled(config('github.repo'))) {
                            $downloadUrl = 'https://github.com/'.urlencode((string) config('github.owner')).'/'.urlencode((string) config('github.repo')).'/archive/refs/tags/'.urlencode((string) $note->version).'.zip';
                        }
                    @endphp
                    @if($downloadUrl)
                        <a href="{{ $downloadUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100">Download package</a>
                    @endif
                    @if($isAdmin && str_starts_with($routeBase, 'backend.') && auth()->user()?->tenant_id && (int) $note->tenant_id === (int) auth()->user()->tenant_id)
                        <a href="{{ route($routeBase . '.updates.edit', $note) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-teal-700 hover:bg-slate-50">Edit</a>
                        <form action="{{ route($routeBase . '.updates.destroy', $note) }}" method="POST" class="inline" onsubmit="return confirm('Delete this update note?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-100" title="Delete">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        </form>
                    @elseif($note->tenant_id === null && auth()->check() && auth()->user()?->tenant_id)
                        <form action="{{ route($routeBase . '.updates.clear-notification') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Dismiss notification</button>
                        </form>
                    @endif
                </div>
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
        </div>
    @empty
        <div class="rounded-2xl bg-white p-6 text-sm text-slate-500 shadow-sm ring-1 ring-slate-200/60">No release notes yet.</div>
    @endforelse
</div>

<div class="mt-4">{{ $notes->links() }}</div>
@endsection
