@extends('backend.layouts.app')
@section('title', 'Health Events')
@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Health Events</h1>
        <p class="mt-1 text-sm text-slate-500">Schedule and manage vaccination drives, health seminars, and check-up programs.</p>
    </div>
    <a href="{{ route('backend.events.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 font-medium text-white shadow-sm transition hover:bg-teal-700 hover:shadow-md">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Event
    </a>
</div>

@if($events->isEmpty())
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <div class="rounded-xl bg-slate-50 px-8 py-20 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-teal-100/60">
                <svg class="h-8 w-8 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-800">No events scheduled</h3>
            <p class="mx-auto mt-3 max-w-sm text-sm text-slate-500">Add vaccination drives, health seminars, or check-up schedules to keep your community informed.</p>
            <a href="{{ route('backend.events.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 font-medium text-white shadow-sm transition hover:bg-teal-700 hover:shadow-md">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Event
            </a>
        </div>
    </div>
@else
    <div class="space-y-4">
        @foreach($events as $e)
        <div class="group rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60 transition hover:shadow-md">
            <div class="flex flex-col sm:flex-row">
                {{-- Date badge --}}
                <div class="flex shrink-0 items-center justify-center bg-gradient-to-br from-teal-500 to-teal-600 p-5 text-center text-white sm:w-28 sm:rounded-l-2xl {{ $e->image_path && auth()->user()->tenant?->hasFeature('announcements_events') ? '' : 'rounded-t-2xl sm:rounded-tr-none' }}">
                    <div>
                        <div class="text-sm font-medium uppercase tracking-wide opacity-90">{{ $e->event_date->format('M') }}</div>
                        <div class="text-3xl font-bold leading-none">{{ $e->event_date->format('d') }}</div>
                        <div class="mt-0.5 text-sm opacity-80">{{ $e->event_date->format('Y') }}</div>
                    </div>
                </div>
                @if(auth()->user()->tenant?->hasFeature('announcements_events') && $e->image_path)
                <div class="shrink-0 sm:w-44">
                    <img src="{{ $e->image_url }}" alt="" class="h-40 w-full object-cover sm:h-full">
                </div>
                @endif
                <div class="flex flex-1 flex-col justify-between p-5">
                    <div>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            @if($e->is_published)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Published
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Draft
                                </span>
                            @endif
                            @if($e->event_date->isPast())
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">Past Event</span>
                            @elseif($e->event_date->isToday())
                                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200/60">Today</span>
                            @endif
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800 group-hover:text-teal-700 transition">{{ $e->title }}</h2>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                            @if($e->event_time)
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ \Carbon\Carbon::parse($e->event_time)->format('g:i A') }}
                            </span>
                            @endif
                            @if($e->location)
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $e->location }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <a href="{{ route('backend.events.show', $e) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View
                        </a>
                        <a href="{{ route('backend.events.edit', $e) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </a>
                        <form action="{{ route('backend.events.destroy', $e) }}" method="POST" class="inline delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" onclick="confirmFormSubmit(this.closest('form'), { title: 'Delete Event', message: 'Are you sure you want to delete this event? This action cannot be undone.', confirmText: 'Delete', type: 'danger' })" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
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
    <div class="mt-6 flex justify-center">{{ $events->links() }}</div>
@endif
@endsection
