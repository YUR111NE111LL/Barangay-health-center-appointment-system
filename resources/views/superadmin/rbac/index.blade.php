@extends('superadmin.layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Roles & Permissions (RBAC)</h1>
    <a href="{{ route('super-admin.tenants.index') }}" class="rounded-xl border border-violet-300 bg-violet-50 px-4 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100">Configure RBAC per tenant →</a>
</div>

<p class="mb-6 text-sm text-slate-600">This page sets <strong>global default</strong> permissions for each role. To override permissions <strong>per tenant</strong> (based on their plan), open a tenant from the list and use <strong>RBAC for this tenant</strong>.</p>
<p class="mb-6 text-sm text-slate-600"><strong>Super Admin</strong> is not listed here: platform Super Admins are not restricted by this permission matrix. Per-tenant RBAC for barangay staff and residents is unchanged.</p>

<div class="mb-6 grid gap-4 lg:grid-cols-2">
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="border-b border-slate-200 px-4 py-3 font-medium text-slate-700">Roles</div>
        <ul class="divide-y divide-slate-100 p-2">
            @foreach($roles as $role)
            <li class="flex items-center justify-between px-3 py-2">
                <span class="font-medium text-slate-800">{{ $role->name }}</span>
                <a href="{{ route('super-admin.rbac.edit', $role) }}" class="text-sm font-medium text-violet-600 hover:text-violet-700">Edit permissions</a>
            </li>
            @endforeach
        </ul>
    </div>
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="border-b border-slate-200 px-4 py-3 font-medium text-slate-700">All permissions</div>
        <ul class="max-h-64 divide-y divide-slate-100 overflow-y-auto p-2">
            @foreach($permissions as $p)
            <li class="px-3 py-2 text-sm text-slate-700">{{ $p->name }}</li>
            @endforeach
        </ul>
    </div>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="border-b border-slate-200 px-4 py-3 font-medium text-slate-700">Roles and their permissions</div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Permissions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @foreach($roles as $role)
                <tr class="hover:bg-slate-50/50">
                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $role->name }}</td>
                    <td class="px-4 py-3">
                        @if($role->permissions->isEmpty())
                            <span class="text-slate-500">None</span>
                        @else
                            @foreach($role->permissions as $p)
                                <span class="mr-1 inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $p->name }}</span>
                            @endforeach
                        @endif
                        <a href="{{ route('super-admin.rbac.edit', $role) }}" class="ml-2 text-sm font-medium text-violet-600 hover:text-violet-700">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
