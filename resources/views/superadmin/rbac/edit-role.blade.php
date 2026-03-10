@extends('superadmin.layouts.app')

@section('title', 'Edit role: ' . $role->name)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Edit role: {{ $role->name }}</h1>
    <a href="{{ route('super-admin.rbac.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to RBAC</a>
</div>

<form action="{{ route('super-admin.rbac.update', $role) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="mb-2 text-lg font-semibold text-slate-800">Permissions</h2>
        <p class="mb-4 text-sm text-slate-500">Select the permissions this role should have.</p>
        @if($permissions->isEmpty())
            <p class="text-sm text-slate-500">No permissions defined. Run <code class="rounded bg-slate-100 px-1 py-0.5">php artisan db:seed --class=RoleAndPermissionSeeder</code> to seed them.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($permissions as $p)
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
                    <input type="checkbox" name="permissions[]" value="{{ $p->name }}" id="perm-{{ $p->id }}" {{ $role->permissions->contains('id', $p->id) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <span class="text-sm text-slate-700">{{ $p->name }}</span>
                </label>
                @endforeach
            </div>
        @endif
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-violet-600 px-4 py-2.5 font-medium text-white hover:bg-violet-700">Update permissions</button>
        <a href="{{ route('super-admin.rbac.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
