@extends('backend.layouts.app')
@section('title', $event->title)
@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <a href="{{ route('backend.events.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Events
    </a>
    <div class="flex gap-2">
        <a href="{{ route('backend.events.edit', $event) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </a>
    </div>
</div>

<article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    @if(auth()->user()->tenant?->hasFeature('announcements_events') && $event->image_path)
    <div class="relative">
        <img src="{{ $event->image_url }}" alt="" class="h-64 w-full object-cover sm:h-80">
        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
    </div>
    @endif
    <div class="p-6 sm:p-8">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            @if($event->is_published)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200/60">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Published
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200/60">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Draft
                </span>
            @endif
            @if($event->event_date->isPast())
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">Past Event</span>
            @elseif($event->event_date->isToday())
                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200/60">Today</span>
            @endif
        </div>

        <h1 class="text-2xl font-bold text-slate-800 sm:text-3xl">{{ $event->title }}</h1>

        <div class="mt-4 flex flex-wrap gap-4">
            <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-2.5 ring-1 ring-slate-200/60">
                <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <div>
                    <p class="text-xs text-slate-500">Date</p>
                    <p class="font-medium text-slate-800">{{ $event->event_date->format('l, F d, Y') }}</p>
                </div>
            </div>
            @if($event->event_time)
            <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-2.5 ring-1 ring-slate-200/60">
                <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="text-xs text-slate-500">Time</p>
                    <p class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($event->event_time)->format('g:i A') }}</p>
                </div>
            </div>
            @endif
            @if($event->location)
            <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-2.5 ring-1 ring-slate-200/60">
                <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <div>
                    <p class="text-xs text-slate-500">Location</p>
                    <p class="font-medium text-slate-800">{{ $event->location }}</p>
                </div>
            </div>
            @endif
        </div>

        <div class="mt-6 whitespace-pre-wrap text-base leading-relaxed text-slate-600">{{ $event->description }}</div>
    </div>
</article>
@endsection
