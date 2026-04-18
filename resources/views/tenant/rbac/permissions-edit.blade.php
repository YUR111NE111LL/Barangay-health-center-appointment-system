@extends('tenant.layouts.app')

@section('title', 'Edit permissions: ' . $role->name)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Edit permissions: {{ $role->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Plan: {{ $tenant->plan?->name ?? '—' }}. Changes apply only to this barangay.</p>
    </div>
    <a href="{{ route('backend.rbac.permissions.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← Back to role permissions</a>
</div>

<form action="{{ route('backend.rbac.permissions.update', $role) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Permissions</h2>
        <p class="mt-1 text-sm text-slate-500">Select what this role can do. You can edit permissions later.</p>
        @if($role->name === 'Resident')
            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-200">Resident accounts use this role. You can assign any permission allowed for your plan (same list as <strong>Add role</strong>). Typical default is <strong>book appointments</strong> until you change it here.</p>
        @endif
        @if($permissions->isEmpty())
            <p class="mt-4 text-sm text-slate-500">No permissions available for your plan.</p>
        @else
            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($permissions as $p)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-slate-300 hover:bg-slate-50/80">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $p->name }}"
                            id="perm-{{ $p->id }}"
                            {{ in_array($p->name, $currentPermissionNames, true) ? 'checked' : '' }}
                            class="h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        >
                        <span class="text-sm lowercase text-slate-600">{{ $p->name }}</span>
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
