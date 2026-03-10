@extends('frontend.layouts.app')
@section('title', $event->title)
@section('content')
<div class="mb-6">
    <a href="{{ route('resident.events.index') }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Back to events</a>
</div>
<article class="{{ ($hasAnnouncementsEvents ?? false) ? 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60' : 'rounded-xl border border-slate-200 bg-white' }}">
    @if(($hasAnnouncementsEvents ?? false) && $event->image_path)
        <img src="{{ $event->image_url }}" alt="" class="w-full object-cover" style="max-height: 320px;">
    @endif
    <div class="{{ ($hasAnnouncementsEvents ?? false) ? 'p-6 sm:p-8' : 'p-4 sm:p-6' }}">
        <h1 class="text-2xl font-bold text-slate-800">{{ $event->title }}</h1>
        <p class="mt-2 text-slate-600">
            {{ $event->event_date->format('l, F d, Y') }}
            @if($event->event_time) {{ \Carbon\Carbon::parse($event->event_time)->format('g:i A') }}@endif
            @if($event->location) &middot; {{ $event->location }}@endif
        </p>
        <div class="mt-4 whitespace-pre-wrap text-slate-700">{{ $event->description }}</div>
    </div>
</article>
@endsection
