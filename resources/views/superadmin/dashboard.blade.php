@extends('superadmin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Super Admin Dashboard</h1>
    <p class="text-slate-500">Global system statistics</p>
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <p class="text-sm font-medium text-slate-500">Tenants</p>
        <p class="mt-2 text-3xl font-bold text-violet-600">{{ $tenantCount }}</p>
        <a href="{{ route('super-admin.tenants.index') }}" class="mt-3 inline-block text-sm font-medium text-violet-600 hover:text-violet-700">Manage</a>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <p class="text-sm font-medium text-slate-500">Total Users</p>
        <p class="mt-2 text-3xl font-bold text-slate-800">{{ $userCount }}</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <p class="text-sm font-medium text-slate-500">Total Appointments</p>
        <p class="mt-2 text-3xl font-bold text-slate-800">{{ $appointmentCount }}</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <p class="text-sm font-medium text-slate-500">This Month</p>
        <p class="mt-2 text-3xl font-bold text-teal-600">{{ $appointmentsThisMonth }}</p>
    </div>
</div>

<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    <h2 class="mb-4 text-lg font-semibold text-slate-800">Quick actions</h2>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('super-admin.tenants.create') }}" class="inline-flex rounded-xl bg-violet-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-violet-700 focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">Add new tenant</a>
        <a href="{{ route('super-admin.tenants.index') }}" class="inline-flex rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">View all tenants</a>
    </div>
</div>
@endsection
