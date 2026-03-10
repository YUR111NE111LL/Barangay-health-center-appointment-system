@extends('backend.layouts.app')

@section('title', 'Users')

@section('content')
@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Users</h1>
        @if($planName !== null)
            <p class="mt-1 text-sm text-slate-500">
                @if($maxUsers === 0)
                    {{ $userCount }} user(s) · {{ $planName }} (unlimited users)
                @else
                    {{ $userCount }} of {{ $maxUsers }} user slots · {{ $planName }}
                    @if(!$canAddUser)
                        <span class="text-amber-600">— at limit</span>
                    @endif
                @endif
            </p>
        @endif
    </div>
    @if($canAddUser)
        <a href="{{ route('backend.users.create') }}" class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-teal-700">Add user</a>
    @else
        <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-500">Add user (plan limit reached)</span>
    @endif
</div>

<form class="mb-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60" method="GET">
    <div class="min-w-[180px]">
        <label class="mb-1 block text-xs font-medium text-slate-500">Search</label>
        <input type="text" name="search" placeholder="Name or email..." value="{{ request('search') }}" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
    </div>
    <div class="min-w-[120px]">
        <label class="mb-1 block text-xs font-medium text-slate-500">Role</label>
        <select name="role" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">All roles</option>
            <option value="Resident" {{ request('role') === 'Resident' ? 'selected' : '' }}>Resident</option>
            <option value="Staff" {{ request('role') === 'Staff' ? 'selected' : '' }}>Staff</option>
            <option value="Nurse" {{ request('role') === 'Nurse' ? 'selected' : '' }}>Nurse</option>
            <option value="Health Center Admin" {{ request('role') === 'Health Center Admin' ? 'selected' : '' }}>Health Center Admin</option>
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Filter</button>
</form>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Profile</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Role</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($users as $u)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-medium text-teal-700" aria-hidden="true">{{ $u->initials }}</span>
                            <span class="text-sm font-medium text-slate-800">{{ $u->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $u->email }}</td>
                    <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $u->role }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-12 text-center text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4 flex justify-center">
    {{ $users->withQueryString()->links() }}
</div>
@endsection
