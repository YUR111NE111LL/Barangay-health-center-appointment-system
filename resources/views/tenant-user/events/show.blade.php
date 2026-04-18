@extends('tenant-user.layouts.app')
@section('title', $event->title)
@section('content')
<div class="mb-6">
    <a href="{{ route('resident.events.index') }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Back to events</a>
</div>
<article class="{{ ($hasAnnouncementsEvents ?? false) ? 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60' : 'rounded-xl border border-slate-200 bg-white' }}">
    @if(($hasAnnouncementsEvents ?? false) && $event->image_path)
        {{-- Limit display size so photos are not stretched to full viewport width/height (reduces blur from upscaling). --}}
        <div class="bg-slate-100 px-4 pt-4 sm:px-6 sm:pt-6">
            <div class="relative mx-auto h-52 max-h-[min(50vh,24rem)] w-full max-w-3xl overflow-hidden rounded-xl bg-slate-200/60 shadow-inner sm:h-60 md:h-64">
                <img
                    src="{{ $event->image_url }}"
                    alt=""
                    class="h-full w-full object-cover object-center"
                    decoding="async"
                    fetchpriority="high"
                    width="1200"
                    height="675"
                >
            </div>
        </div>
    @endif
    <div class="{{ ($hasAnnouncementsEvents ?? false) ? 'p-6 sm:p-8' : 'p-4 sm:p-6' }}">
        <h1 class="text-2xl font-bold text-slate-800">{{ $event->title }}</h1>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-teal-100 px-3 py-2 text-sm font-semibold text-teal-950 shadow-sm ring-1 ring-teal-200/80 sm:text-base">
                <svg class="h-5 w-5 shrink-0 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ $event->event_date->format('l, F d, Y') }}
                @if($event->event_time)
                    <span class="font-medium text-teal-800">&middot; {{ \Carbon\Carbon::parse($event->event_time)->format('g:i A') }}</span>
                @endif
            </span>
            @if($event->location)
                <span class="inline-flex max-w-full min-w-0 items-center gap-1.5 rounded-lg border border-teal-200/90 bg-teal-50 px-3 py-2 text-sm font-semibold text-teal-950 shadow-sm ring-1 ring-teal-100/80 sm:text-base">
                    <svg class="h-5 w-5 shrink-0 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="min-w-0">{{ $event->location }}</span>
                </span>
            @endif
        </div>
        <div class="mt-4 whitespace-pre-wrap text-slate-700">{{ $event->description }}</div>
    </div>
</article>
@endsection
