@extends('tenant-user.layouts.app')
@section('title', 'Health Events')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Health Events</h1>
    <p class="mt-1 text-slate-500">Upcoming vaccination drives, health seminars, and check-up schedules.</p>
</div>
<div class="{{ ($hasAnnouncementsEvents ?? false) ? 'space-y-4' : 'divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white' }}">
    @forelse($events as $e)
    <article class="{{ ($hasAnnouncementsEvents ?? false) ? 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60' : 'p-4' }}">
        @if(($hasAnnouncementsEvents ?? false) && $e->image_path)
            <a href="{{ route('resident.events.show', $e) }}" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2">
                {{-- Capped height + max width so small uploads are not upscaled to a huge box (looks blurry). --}}
                <div class="bg-slate-100 px-4 pb-0 pt-4 sm:px-6">
                    <div class="relative mx-auto h-44 max-h-[38vh] w-full max-w-2xl overflow-hidden rounded-xl bg-slate-200/60 shadow-inner sm:h-48">
                        <img
                            src="{{ $e->image_url }}"
                            alt=""
                            class="h-full w-full object-cover object-center"
                            loading="lazy"
                            decoding="async"
                            width="960"
                            height="540"
                        >
                    </div>
                </div>
            </a>
        @endif
        <div class="{{ ($hasAnnouncementsEvents ?? false) ? 'p-4 sm:p-6' : '' }}">
            <h2 class="text-lg font-semibold text-slate-800">
                <a href="{{ route('resident.events.show', $e) }}" class="hover:text-teal-600">{{ $e->title }}</a>
            </h2>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-teal-100 px-3 py-1.5 text-sm font-semibold text-teal-950 shadow-sm ring-1 ring-teal-200/80">
                    <svg class="h-4 w-4 shrink-0 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ $e->event_date->format('l, M d, Y') }}
                    @if($e->event_time)
                        <span class="font-medium text-teal-800">&middot; {{ \Carbon\Carbon::parse($e->event_time)->format('g:i A') }}</span>
                    @endif
                </span>
                @if($e->location)
                    <span class="inline-flex max-w-full min-w-0 items-center gap-1.5 rounded-lg border border-teal-200/90 bg-teal-50 px-3 py-1.5 text-sm font-semibold text-teal-900 shadow-sm ring-1 ring-teal-100/80">
                        <svg class="h-4 w-4 shrink-0 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="truncate">{{ $e->location }}</span>
                    </span>
                @endif
            </div>
            <p class="mt-2 text-slate-600">{{ Str::limit($e->description, 150) }}</p>
            <a href="{{ route('resident.events.show', $e) }}" class="mt-3 inline-block text-sm font-medium text-teal-600 hover:text-teal-700">View details</a>
        </div>
    </article>
    @empty
    <div class="rounded-xl border border-slate-200 bg-white p-8 text-center">
        <p class="text-slate-500">No upcoming events. Check back later.</p>
    </div>
    @endforelse
</div>
<div class="mt-6">{{ $events->links() }}</div>
@endsection
