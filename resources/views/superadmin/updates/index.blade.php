@extends('superadmin.layouts.app')

@section('title', 'Global Updates')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Global Updates</h1>
        <p class="mt-1 text-sm text-slate-500">Publish one update for all tenants and resident portals.</p>
        @if(!empty($latestVersionNote?->version))
            <p class="mt-2 inline-flex items-center gap-2 rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">
                Latest version: v{{ $latestVersionNote->version }}
                @if($latestVersionNote->published_at)
                    <span class="font-normal text-violet-600">({{ $latestVersionNote->published_at->format('M d, Y h:i A') }})</span>
                @endif
            </p>
        @endif
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <form action="{{ route('super-admin.updates.sync-github') }}" method="POST" class="inline" onsubmit="return confirm('Fetch latest releases from GitHub and run update commands now?');">
            @csrf
            <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Sync from GitHub</button>
        </form>
        <a href="{{ route('super-admin.updates.create') }}" class="rounded-xl bg-violet-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-violet-800">Publish global update</a>
    </div>
</div>

<div class="mb-6 rounded-2xl border p-4 shadow-sm ring-1 {{ !empty($releaseStatus['ok']) ? (!empty($releaseStatus['has_update']) ? 'border-emerald-200 bg-emerald-50 ring-emerald-200/70' : 'border-slate-200 bg-slate-50 ring-slate-200/70') : 'border-amber-200 bg-amber-50 ring-amber-200/70' }}">
    <h2 class="text-sm font-semibold uppercase tracking-wide {{ !empty($releaseStatus['ok']) ? (!empty($releaseStatus['has_update']) ? 'text-emerald-700' : 'text-slate-600') : 'text-amber-700' }}">Release status</h2>
    <p class="mt-1 text-sm {{ !empty($releaseStatus['ok']) ? (!empty($releaseStatus['has_update']) ? 'text-emerald-800' : 'text-slate-700') : 'text-amber-800' }}">
        {{ $releaseStatus['message'] ?? 'Release status unavailable.' }}
    </p>
    @if(!empty($releaseStatus['latest_github_title']))
        <p class="mt-2 text-xs {{ !empty($releaseStatus['ok']) ? 'text-slate-700' : 'text-amber-700' }}">
            Latest GitHub release title: <strong>{{ $releaseStatus['latest_github_title'] }}</strong>
        </p>
    @endif
    @if(!empty($releaseStatus['latest_github_version']) || !empty($releaseStatus['latest_local_version']))
        <p class="mt-2 text-xs {{ !empty($releaseStatus['ok']) ? 'text-slate-600' : 'text-amber-700' }}">
            GitHub: <strong>{{ $releaseStatus['latest_github_version'] ?? '—' }}</strong>
            <span class="mx-2">|</span>
            Local: <strong>{{ $releaseStatus['latest_local_version'] ?? '—' }}</strong>
        </p>
    @endif
    <p class="mt-1 text-xs {{ !empty($releaseStatus['ok']) ? 'text-slate-600' : 'text-amber-700' }}">
        Current system version: <strong>{{ $releaseStatus['current_app_version'] ?? '—' }}</strong>
    </p>
</div>

<div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-200/60">
    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Update command flow</h2>
    <p class="mt-2 text-sm text-slate-600">Clicking <strong>Sync from GitHub</strong> now runs the command flow below automatically. You can still run these manually when needed.</p>
    <pre class="mt-3 overflow-auto rounded-xl bg-slate-900 p-3 text-xs leading-relaxed text-slate-100"><code>git pull
composer install
npm install
php artisan migrate
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
npm run build</code></pre>
</div>

<div class="space-y-4">
    @forelse($notes as $note)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold text-slate-800">{{ $note->title }}</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ strtoupper($note->type) }}</span>
                @if($note->version)
                    <span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700">v{{ $note->version }}</span>
                @endif
                @if($note->is_pinned)
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Pinned</span>
                @endif
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">All tenants</span>
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
            <div class="mt-3 flex gap-2">
                <a href="{{ route('super-admin.updates.edit', $note) }}" class="text-sm font-medium text-violet-600 hover:text-violet-700">Edit</a>
                @php
                    $isGitHubNote = is_string($note->external_ref ?? null) && str_starts_with((string) $note->external_ref, 'github:');
                    $downloadUrl = null;
                    if ($isGitHubNote && filled($note->version) && filled(config('github.owner')) && filled(config('github.repo'))) {
                        $downloadUrl = 'https://github.com/'.urlencode((string) config('github.owner')).'/'.urlencode((string) config('github.repo')).'/archive/refs/tags/'.urlencode((string) $note->version).'.zip';
                    }
                @endphp
                @if($downloadUrl)
                    <a href="{{ $downloadUrl }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Download package</a>
                @endif
                <form action="{{ route('super-admin.updates.destroy', $note) }}" method="POST" onsubmit="return confirm('Delete this global update?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-700">Delete</button>
                </form>
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white p-6 text-sm text-slate-500 shadow-sm ring-1 ring-slate-200/60">No global updates yet.</div>
    @endforelse
</div>

<div class="mt-4">{{ $notes->links() }}</div>
@endsection
