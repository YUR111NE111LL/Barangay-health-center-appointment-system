@extends('tenant.layouts.app')

@section('title', 'Edit permissions: ' . $role->name)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Edit permissions: {{ $role->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Only permissions allowed by your plan ({{ $tenant->plan?->name ?? 'plan' }}) are shown. Uncheck to disable for this role.</p>
    </div>
    <a href="{{ route('backend.rbac.permissions.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← Back to role permissions</a>
</div>

<form action="{{ route('backend.rbac.permissions.update', $role) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="mb-2 text-lg font-semibold text-slate-800">Permissions</h2>
        <p class="mb-4 text-sm text-slate-500">Select what this role can do. Unchecked permissions are disabled for users with this role in your barangay.</p>
        @if($role->name === 'Resident')
        <p class="mb-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-200">Resident role is for barangay residents (e.g. book appointments). It is separate from Staff and Nurse roles.</p>
        @endif
        @if($permissions->isEmpty())
            <p class="text-sm text-slate-500">No permissions available for your plan.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($permissions as $p)
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
                    <input type="checkbox" name="permissions[]" value="{{ $p->name }}" id="perm-{{ $p->id }}" {{ in_array($p->name, $currentPermissionNames, true) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                    <span class="text-sm text-slate-700">{{ $p->name }}</span>
                </label>
                @endforeach
            </div>
        @endif
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Update permissions</button>
        <a href="{{ route('backend.rbac.permissions.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
