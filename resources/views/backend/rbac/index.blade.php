@extends('backend.layouts.app')

@section('title', 'User roles')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">User roles (RBAC)</h1>
    <p class="mt-1 text-slate-500">Assign roles to users in your barangay. Only Barangay (Health Center) Admin can view and manage roles; Nurses and Residents cannot access this page.</p>
    <p class="mt-1 text-sm text-slate-500">Role permissions (what each role can do) are set by the platform based on your barangay's plan and apply only to this barangay—they do not affect other tenants.</p>
    <p class="mt-3">
        <a href="{{ route('backend.rbac.permissions.index') }}" class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Manage role permissions (enable/disable per role)</a>
    </p>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Current role</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($users as $u)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-4 py-3 text-sm text-slate-800">{{ $u->name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $u->email }}</td>
                    <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $u->role }}</span></td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('backend.rbac.edit', $u) }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Change role</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-12 text-center text-slate-500">No users in this barangay.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
