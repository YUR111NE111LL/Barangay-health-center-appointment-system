@extends('superadmin.layouts.app')

@section('title', 'Super Admin Users')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Super Admin Users</h1>
        <p class="mt-1 text-sm text-slate-500">Manage Super Admin accounts for the platform.</p>
    </div>
    <a href="{{ route('super-admin.users.create') }}" class="rounded-xl bg-violet-600 px-4 py-2.5 font-medium text-white hover:bg-violet-700">Add Super Admin</a>
</div>

@if(session('success'))
    <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
        {{ session('success') }}
    </div>
@endif

@if(session('info'))
    <div class="mb-4 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-700 ring-1 ring-blue-200">
        {{ session('info') }}
    </div>
@endif

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Profile</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Login Method</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Created</th>
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
                    <td class="px-4 py-3 text-sm text-slate-600">
                        @if($u->google_id)
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                                Google
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">Email & Password</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500">{{ $u->created_at ? $u->created_at->format('M j, Y') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-12 text-center text-slate-500">No Super Admin users yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">
            {{ $users->links() }}
        </div>
    @endif
</div>
<a href="{{ route('super-admin.dashboard') }}" class="mt-4 inline-block text-sm font-medium text-violet-600 hover:text-violet-700">← Back to Dashboard</a>
@endsection
