@extends('tenant.layouts.app')

@section('title', 'Add role')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Add role</h1>
        <p class="mt-1 text-sm text-slate-500">Create a custom role and assign permissions allowed by your plan ({{ $tenant->plan?->name ?? 'plan' }}).</p>
    </div>
    <a href="{{ route('backend.rbac.permissions.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← Back to role permissions</a>
</div>

<form action="{{ route('backend.rbac.permissions.store') }}" method="POST" class="space-y-6">
    @csrf
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <div>
            <label for="role_name" class="mb-1 block text-sm font-medium text-slate-700">Role name <span class="text-rose-500">*</span></label>
            <input type="text" name="role_name" id="role_name" value="{{ old('role_name') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
            @error('role_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <h2 class="mb-2 mt-6 text-lg font-semibold text-slate-800">Permissions</h2>
        <p class="mb-4 text-sm text-slate-500">Select what this role can do. You can edit permissions later.</p>
        @if($permissions->isEmpty())
            <p class="text-sm text-slate-500">No permissions available for your plan.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($permissions as $p)
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
                    <input type="checkbox" name="permissions[]" value="{{ $p->name }}" {{ in_array($p->name, old('permissions', []), true) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                    <span class="text-sm text-slate-700">{{ $p->name }}</span>
                </label>
                @endforeach
            </div>
            @error('permissions')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            @error('permissions.*')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        @endif
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Create role</button>
        <a href="{{ route('backend.rbac.permissions.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
