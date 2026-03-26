@extends('backend.layouts.app')

@section('title', 'Appointment #' . $appointment->id)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Appointment #{{ $appointment->id }}</h1>
    <div class="flex flex-wrap gap-2">
        @if(auth()->user()->hasTenantPermission('encode appointments') || auth()->user()->hasTenantPermission('update visit status') || auth()->user()->hasTenantPermission('record notes'))
        <a href="{{ route('backend.appointments.edit', $appointment) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 font-medium text-slate-700 hover:bg-slate-50">Edit</a>
        @endif
        @if(auth()->user()->hasTenantPermission('approve appointments'))
        @if($appointment->status === 'pending')
            <form action="{{ route('backend.appointments.approve', $appointment) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">Approve</button>
            </form>
        @endif
        @endif
    </div>
</div>

<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    <dl class="grid gap-4 sm:grid-cols-2">
        <div><dt class="text-sm font-medium text-slate-500">Patient</dt><dd class="mt-1 text-slate-800">{{ $appointment->resident->name }} ({{ $appointment->resident->email }})</dd></div>
        <div><dt class="text-sm font-medium text-slate-500">Service</dt><dd class="mt-1 text-slate-800">{{ $appointment->service->name }}</dd></div>
        <div><dt class="text-sm font-medium text-slate-500">Date</dt><dd class="mt-1 text-slate-800">{{ $appointment->scheduled_date->format('l, F j, Y') }}</dd></div>
        <div><dt class="text-sm font-medium text-slate-500">Time</dt><dd class="mt-1 text-slate-800">{{ \Carbon\Carbon::parse($appointment->scheduled_time)->format('g:i A') }}</dd></div>
        <div><dt class="text-sm font-medium text-slate-500">Status</dt>
            <dd class="mt-1">
                @if($appointment->status === 'approved')
                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-sm font-medium text-emerald-800">approved</span>
                @elseif($appointment->status === 'pending')
                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-sm font-medium text-amber-800">pending</span>
                @else
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-sm font-medium text-slate-700">{{ $appointment->status }}</span>
                @endif
            </dd>
        </div>
        @if($appointment->complaint)
        <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">Complaint</dt><dd class="mt-1 text-slate-800">{{ $appointment->complaint }}</dd></div>
        @endif
        @if($appointment->notes)
        <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">Notes</dt><dd class="mt-1 text-slate-800">{{ $appointment->notes }}</dd></div>
        @endif
        @if($appointment->approved_at)
        <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">Approved</dt><dd class="mt-1 text-sm text-slate-600">By {{ $appointment->approvedByUser?->name ?? 'N/A' }} on {{ $appointment->approved_at->format('M j, Y g:i A') }}</dd></div>
        @endif
    </dl>
</div>
<a href="{{ route('backend.appointments.index') }}" class="mt-4 inline-block text-sm font-medium text-teal-600 hover:text-teal-700">← Back to list</a>
@endsection
