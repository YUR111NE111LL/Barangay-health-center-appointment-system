@extends('superadmin.layouts.app')

@section('title', 'Super Admin Accounts')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Super Admin Accounts</h1>
    <p class="mt-1 text-sm text-slate-500">Accounts registered as Super Admin (platform administrators).</p>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Profile</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Signed up</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($users as $u)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-sm font-medium text-violet-700" aria-hidden="true">{{ $u->initials }}</span>
                            <span class="text-sm font-medium text-slate-800">{{ $u->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $u->email }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500">{{ $u->created_at ? $u->created_at->format('M j, Y') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-12 text-center text-slate-500">No Super Admin accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<a href="{{ route('super-admin.dashboard') }}" class="mt-4 inline-block text-sm font-medium text-violet-600 hover:text-violet-700">← Back to Dashboard</a>
@endsection
