@extends('frontend.layouts.app')

@section('title', 'My Appointments')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-800">My Appointments</h1>
    @if($canBook ?? auth()->user()->hasTenantPermission('book appointments'))
    <a href="{{ route('resident.book') }}" class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-teal-700">Book New Appointment</a>
    @else
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200/60">
        <strong>Book appointment</strong> — Your health center admin has disabled this for your account. RBAC permissions are set per tenant and plan. Contact your health center if you need this access.
    </div>
    @endif
</div>

@if($announcements->isNotEmpty() || $upcomingEvents->isNotEmpty())
<div class="mb-8 grid gap-6 sm:grid-cols-2">
    @if($announcements->isNotEmpty())
    <div class="{{ ($hasAnnouncementsEvents ?? false) ? 'rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60' : 'rounded-xl border border-slate-200 bg-white p-4' }}">
        <h2 class="text-lg font-semibold text-slate-800">Health Announcements</h2>
        <ul class="mt-3 space-y-2">
            @foreach($announcements as $a)
            <li>
                <a href="{{ route('resident.announcements.show', $a) }}" class="font-medium text-teal-600 hover:text-teal-700">{{ $a->title }}</a>
                <span class="text-xs text-slate-400">{{ $a->created_at->format('M d') }}</span>
            </li>
            @endforeach
        </ul>
        <a href="{{ route('resident.announcements.index') }}" class="mt-2 inline-block text-sm font-medium text-teal-600 hover:text-teal-700">View all</a>
    </div>
    @endif
    @if($upcomingEvents->isNotEmpty())
    <div class="{{ ($hasAnnouncementsEvents ?? false) ? 'rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60' : 'rounded-xl border border-slate-200 bg-white p-4' }}">
        <h2 class="text-lg font-semibold text-slate-800">Upcoming Events</h2>
        <ul class="mt-3 space-y-2">
            @foreach($upcomingEvents as $e)
            <li>
                <a href="{{ route('resident.events.show', $e) }}" class="font-medium text-teal-600 hover:text-teal-700">{{ $e->title }}</a>
                <span class="text-xs text-slate-400">{{ $e->event_date->format('M d') }}@if($e->location) &middot; {{ $e->location }}@endif</span>
            </li>
            @endforeach
        </ul>
        <a href="{{ route('resident.events.index') }}" class="mt-2 inline-block text-sm font-medium text-teal-600 hover:text-teal-700">View all</a>
    </div>
    @endif
</div>
@endif

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    @if($appointments->isEmpty())
        <div class="p-8 text-center">
            <p class="text-slate-500">You have no appointments yet.</p>
            @if($canBook ?? auth()->user()->hasTenantPermission('book appointments'))
            <a href="{{ route('resident.book') }}" class="mt-2 inline-block font-medium text-teal-600 hover:text-teal-700">Book one now</a>
            @else
            <p class="mt-2 text-sm text-slate-500">Your health center admin has disabled booking for your account. Permissions are set per tenant and plan—contact your health center if you need this access.</p>
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Service</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($appointments as $apt)
                    <tr class="hover:bg-slate-50/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $apt->scheduled_date->format('M d, Y') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ \Carbon\Carbon::parse($apt->scheduled_time)->format('g:i A') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $apt->service->name }}</td>
                        <td class="px-4 py-3">
                            @if($apt->status === 'approved')
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">approved</span>
                            @elseif($apt->status === 'pending')
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">pending</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $apt->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-3">
            {{ $appointments->links() }}
        </div>
    @endif
</div>
@endsection
