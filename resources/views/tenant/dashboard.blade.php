@extends('tenant.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
    <p class="text-slate-500">{{ auth()->user()->tenant?->name }}</p>
</div>
<div class="hidden" data-dashboard-live data-poll-url="{{ route('backend.dashboard.live.summary') }}" data-context="summary" data-csrf="{{ csrf_token() }}"></div>
<div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <p class="text-sm font-medium text-slate-500">Today's Appointments</p>
        <p class="mt-2 text-3xl font-bold text-teal-600" data-live-stat="todayCount">{{ $todayCount }}</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <p class="text-sm font-medium text-slate-500">Pending Approval</p>
        <p class="mt-2 text-3xl font-bold text-amber-600" data-live-stat="pendingCount">{{ $pendingCount }}</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <p class="text-sm font-medium text-slate-500">Approved Today</p>
        <p class="mt-2 text-3xl font-bold text-emerald-600" data-live-stat="approvedToday">{{ $approvedToday }}</p>
    </div>
</div>
<div class="mt-6 flex flex-wrap gap-3">
    <a href="{{ route('backend.appointments.index') }}" class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">View All Appointments</a>
    <a href="{{ route('backend.reports.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 shadow-sm hover:bg-slate-50">Reports</a>
</div>
@endsection
