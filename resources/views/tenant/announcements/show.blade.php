@extends('tenant.layouts.app')

@section('title', $announcement->title)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <a href="{{ route('backend.announcements.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Announcements
    </a>
    <div class="flex gap-2">
        <a href="{{ route('backend.announcements.edit', $announcement) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </a>
    </div>
</div>

<article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    @if(auth()->user()->tenant?->hasFeature('announcements_events') && $announcement->image_path)
    <div class="relative">
        <img src="{{ $announcement->image_url }}" alt="" class="h-64 w-full object-cover sm:h-80">
        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
    </div>
    @endif
    <div class="p-6 sm:p-8">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            @if($announcement->is_published)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200/60">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Published
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200/60">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Draft
                </span>
            @endif
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-400">
                <span class="inline-flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ $announcement->created_at->format('F d, Y \a\t g:i A') }}
                </span>
                @if($announcement->creator)
                    <span class="text-slate-500">{{ $announcement->creator->name }}</span>
                @endif
            </div>
        </div>
        <h1 class="text-2xl font-bold text-slate-800 sm:text-3xl">{{ $announcement->title }}</h1>
        <div class="mt-6 whitespace-pre-wrap text-base leading-relaxed text-slate-600">{{ $announcement->body }}</div>
    </div>
</article>
@endsection
