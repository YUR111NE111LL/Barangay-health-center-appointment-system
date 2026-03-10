@extends('superadmin.layouts.app')

@section('title', 'Pending approvals')

@section('content')
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Pending approvals</h1>
        <p class="mt-1 text-slate-500">Barangay Admin and Super Admin signups need your approval. Staff and Nurse are approved by each barangay admin.</p>
    </div>
</div>

<div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60 overflow-hidden">
    @if($pending->isEmpty())
        <div class="px-6 py-12 text-center text-slate-500">No pending approvals.</div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Barangay / Tenant</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Signed up</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($pending as $u)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $u->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $u->email }}</td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-800">{{ $u->role }}</span></td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $u->tenant?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $u->created_at->format('M j, Y g:i A') }}</td>
                        <td class="px-4 py-3 text-right">
                            <form action="{{ route('super-admin.pending-approvals.approve', $u) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700">Approve</button>
                            </form>
                            <form action="{{ route('super-admin.pending-approvals.deny', $u) }}" method="POST" class="inline ml-2">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="confirmFormSubmit(this.closest('form'), { title: 'Deny Registration', message: 'Are you sure you want to deny and remove this registration? This cannot be undone.', confirmText: 'Deny', type: 'danger' })" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Deny</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
