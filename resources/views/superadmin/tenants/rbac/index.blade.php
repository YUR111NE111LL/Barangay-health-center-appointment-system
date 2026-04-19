@extends('superadmin.layouts.app')

@section('title', 'RBAC: ' . $tenant->name)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Roles & Permissions</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $tenant->name }} · Plan: {{ $tenant->plan?->name ?? '—' }}</p>
    </div>
    <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← Back to tenant</a>
</div>

@if(empty($tenantHasAnyRbac))
<p class="mb-4 rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">Using global defaults. Edit a role and save to set permissions for this tenant; after that, only checked permissions are allowed.</p>
@endif
<div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="border-b border-slate-200 px-4 py-3 font-medium text-slate-700">Permissions per role (based on plan)</div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Permissions</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @foreach($roles as $roleModel)
                @php $roleName = $roleModel->name; $perms = $permissionsByRole[$roleName] ?? []; @endphp
                <tr class="hover:bg-slate-50/50">
                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $roleName }}</td>
                    <td class="px-4 py-3">
                        @if(empty($perms))
                            <span class="text-slate-500">None</span>
                        @else
                            @foreach($perms as $p)
                                <span class="mr-1 inline-flex rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-700">{{ config('bhcas.permission_help.'.$p.'.label', str($p)->headline()) }}</span>
                            @endforeach
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('super-admin.tenants.rbac.edit', [$tenant, $roleModel]) }}" class="text-sm font-medium text-violet-600 hover:text-violet-700">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
