@extends('backend.layouts.app')

@section('title', 'Reports')

@section('content')
<h1 class="mb-2 text-2xl font-bold text-slate-800">Reports</h1>
<p class="mb-6 text-slate-500">{{ $tenant->name }}</p>

<form class="mb-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60" method="GET">
    <div class="min-w-[140px]">
        <label class="mb-1 block text-xs font-medium text-slate-500">From</label>
        <input type="date" name="from" value="{{ $from }}" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
    </div>
    <div class="min-w-[140px]">
        <label class="mb-1 block text-xs font-medium text-slate-500">To</label>
        <input type="date" name="to" value="{{ $to }}" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
    </div>
    <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Apply</button>
</form>

<div class="mb-6 grid gap-4 sm:grid-cols-2">
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="border-b border-slate-200 px-4 py-3 font-medium text-slate-700">By status</div>
        <ul class="divide-y divide-slate-100 p-2">
            @forelse($byStatus as $status => $count)
                <li class="flex items-center justify-between px-2 py-2 text-sm">
                    <span class="capitalize text-slate-700">{{ $status }}</span>
                    <span class="rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-800">{{ $count }}</span>
                </li>
            @empty
                <li class="px-2 py-4 text-center text-sm text-slate-500">No data</li>
            @endforelse
        </ul>
    </div>
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="border-b border-slate-200 px-4 py-3 font-medium text-slate-700">By service</div>
        <ul class="divide-y divide-slate-100 p-2">
            @forelse($byServiceDisplay as $row)
                <li class="flex items-center justify-between px-2 py-2 text-sm">
                    <span class="text-slate-700">{{ $row['name'] }}</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $row['count'] }}</span>
                </li>
            @empty
                <li class="px-2 py-4 text-center text-sm text-slate-500">No data</li>
            @endforelse
        </ul>
    </div>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="border-b border-slate-200 px-4 py-3 font-medium text-slate-700">Appointments ({{ $from }} – {{ $to }})</div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Service</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($appointments as $apt)
                <tr class="hover:bg-slate-50/50">
                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $apt->scheduled_date->format('M d') }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ \Carbon\Carbon::parse($apt->scheduled_time)->format('g:i A') }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $apt->resident->name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $apt->service->name }}</td>
                    <td class="whitespace-nowrap px-4 py-3">
                        @if($apt->status === 'approved')
                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">approved</span>
                        @elseif($apt->status === 'pending')
                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">pending</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $apt->status }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No appointments in this range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
