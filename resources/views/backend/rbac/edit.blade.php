@extends('backend.layouts.app')

@section('title', 'Assign role: ' . $user->name)

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Assign role</h1>
    <a href="{{ route('backend.rbac.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to roles</a>
</div>
<p class="mb-6 text-slate-500">{{ $user->name }} ({{ $user->email }})</p>

<form action="{{ route('backend.rbac.update', $user) }}" method="POST" class="max-w-md space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    @csrf
    @method('PUT')
    <div>
        <label for="role" class="mb-1 block text-sm font-medium text-slate-700">Role <span class="text-rose-500">*</span></label>
        <select name="role" id="role" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
            @foreach($roles as $value => $label)
                <option value="{{ $value }}" {{ old('role', $user->role) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Update role</button>
        <a href="{{ route('backend.rbac.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
