@extends('tenant.layouts.app')

@section('title', 'Role permissions')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Role permissions</h1>
        <p class="mt-1 text-sm text-slate-500">Choose what each role can do. Based on your plan: {{ $tenant->plan?->name ?? '—' }}. Changes apply only to this barangay.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.rbac.permissions.create') }}" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Add role</a>
        <a href="{{ route('backend.rbac.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← Back to user roles</a>
    </div>
</div>

@if(empty($tenantHasAnyRbac))
<p class="mb-4 rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">Using defaults. Edit a role and save to set permissions for this barangay; after that, only checked permissions are allowed.</p>
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
                                <span class="mr-1 inline-flex rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-700">{{ $p }}</span>
                            @endforeach
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('backend.rbac.permissions.edit', $roleModel) }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Edit</a>
                        @if(!in_array($roleName, ['Super Admin', 'Health Center Admin'], true))
                            <form action="{{ route('backend.rbac.permissions.destroy', $roleModel) }}" method="POST" class="ml-3 inline" onsubmit="return confirm('Delete this role?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-700">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
