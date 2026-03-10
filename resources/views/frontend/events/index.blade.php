@extends('frontend.layouts.app')
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
            <a href="{{ route('resident.events.show', $e) }}" class="block">
                <img src="{{ $e->image_url }}" alt="" class="h-48 w-full object-cover">
            </a>
        @endif
        <div class="{{ ($hasAnnouncementsEvents ?? false) ? 'p-4 sm:p-6' : '' }}">
            <h2 class="text-lg font-semibold text-slate-800">
                <a href="{{ route('resident.events.show', $e) }}" class="hover:text-teal-600">{{ $e->title }}</a>
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ $e->event_date->format('l, M d, Y') }}
                @if($e->event_time) {{ \Carbon\Carbon::parse($e->event_time)->format('g:i A') }}@endif
                @if($e->location) &middot; {{ $e->location }}@endif
            </p>
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
