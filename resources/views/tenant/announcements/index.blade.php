@extends('tenant.layouts.app')

@section('title', 'Health Announcements')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Health Announcements</h1>
        <p class="mt-1 text-sm text-slate-500">Keep residents informed about health updates, vaccination drives, and wellness tips.</p>
    </div>
    <a href="{{ route('backend.announcements.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 font-medium text-white shadow-sm transition hover:bg-teal-700 hover:shadow-md">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Announcement
    </a>
</div>

@if($announcements->isEmpty())
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <div class="rounded-xl bg-slate-50 px-8 py-20 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-teal-100/60">
                <svg class="h-8 w-8 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-800">No announcements yet</h3>
            <p class="mx-auto mt-3 max-w-sm text-sm text-slate-500">Create your first announcement to inform residents about health updates, vaccination drives, or wellness tips.</p>
            <a href="{{ route('backend.announcements.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 font-medium text-white shadow-sm transition hover:bg-teal-700 hover:shadow-md">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Announcement
            </a>
        </div>
    </div>
@else
    <div class="space-y-4">
        @foreach($announcements as $a)
        <div class="group rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60 transition hover:shadow-md">
            <div class="flex flex-col sm:flex-row">
                @if(auth()->user()->tenant?->hasFeature('announcements_events') && $a->image_path)
                <div class="shrink-0 sm:w-48">
                    <img src="{{ $a->image_url }}" alt="" class="h-40 w-full rounded-t-2xl object-cover sm:h-full sm:rounded-l-2xl sm:rounded-tr-none">
                </div>
                @endif
                <div class="flex flex-1 flex-col justify-between p-5">
                    <div>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            @if($a->is_published)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Published
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Draft
                                </span>
                            @endif
                            <span class="text-xs text-slate-400">{{ $a->created_at->format('M d, Y') }}</span>
                            @if($a->creator)
                                <span class="text-xs text-slate-400">&middot; {{ $a->creator->name }}</span>
                            @endif
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800 group-hover:text-teal-700 transition">{{ $a->title }}</h2>
                        <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ Str::limit(strip_tags($a->body), 150) }}</p>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <a href="{{ route('backend.announcements.show', $a) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View
                        </a>
                        <a href="{{ route('backend.announcements.edit', $a) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </a>
                        <form action="{{ route('backend.announcements.destroy', $a) }}" method="POST" class="inline delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" onclick="confirmFormSubmit(this.closest('form'), { title: 'Delete Announcement', message: 'Are you sure you want to delete this announcement? This action cannot be undone.', confirmText: 'Delete', type: 'danger' })" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-6 flex justify-center">
        {{ $announcements->links() }}
    </div>
@endif
@endsection
