@extends('backend.layouts.app')

@section('title', 'Staff Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Barangay Staff</h1>
    <p class="text-slate-500">{{ $tenant->name }} – Today's appointments</p>
</div>
<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    @if($todayAppointments->isEmpty())
        <p class="p-6 text-slate-500">No appointments today.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Service</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($todayAppointments as $apt)
                    <tr class="hover:bg-slate-50/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ \Carbon\Carbon::parse($apt->scheduled_time)->format('g:i A') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $apt->resident->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $apt->service->name }}</td>
                        <td class="px-4 py-3">
                            @if($apt->status === 'approved')
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">approved</span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">pending</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('backend.appointments.show', $apt) }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
<a href="{{ route('backend.appointments.create') }}" class="mt-4 inline-flex rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">New walk-in / Encode</a>
@endsection
