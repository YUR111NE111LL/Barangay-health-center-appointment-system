@extends('superadmin.layouts.app')

@section('title', 'Tenant requests')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Tenant requests</h1>
        <p class="mt-1 text-sm text-slate-600">Public applications to add a new barangay. Approve to create the tenant, or reject with an optional note.</p>
    </div>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('super-admin.tenant-applications.index') }}" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === null ? 'bg-violet-100 text-violet-900' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">All</a>
    <a href="{{ route('super-admin.tenant-applications.index', ['status' => 'pending']) }}" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === 'pending' ? 'bg-violet-100 text-violet-900' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Pending</a>
    <a href="{{ route('super-admin.tenant-applications.index', ['status' => 'approved']) }}" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === 'approved' ? 'bg-violet-100 text-violet-900' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Approved</a>
    <a href="{{ route('super-admin.tenant-applications.index', ['status' => 'rejected']) }}" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === 'rejected' ? 'bg-violet-100 text-violet-900' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Rejected</a>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Submitted</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Domain</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Plan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($applications as $a)
                    <tr class="hover:bg-slate-50/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ \App\Support\DateDisplay::format($a->created_at) }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $a->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $a->domain ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $a->plan?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($a->status === \App\Models\TenantApplication::STATUS_PENDING)
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900">Pending</span>
                            @elseif($a->status === \App\Models\TenantApplication::STATUS_APPROVED)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Approved</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-800">Rejected</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <a href="{{ route('super-admin.tenant-applications.show', $a) }}" class="font-medium text-violet-600 hover:text-violet-800">View</a>
                            <form action="{{ route('super-admin.tenant-applications.destroy', $a) }}" method="POST" class="inline ml-3">
                                @csrf
                                @method('DELETE')
                                @if($status !== null && $status !== '')
                                    <input type="hidden" name="redirect_status" value="{{ $status }}">
                                @endif
                                <button
                                    type="button"
                                    class="font-medium text-rose-600 hover:text-rose-800"
                                    data-confirm-title="{{ e(__('Remove application record')) }}"
                                    data-confirm-message="{{ e(__('Remove this row from the list? If a tenant was already created, it stays under Tenants.')) }}"
                                    data-confirm-text="{{ e(__('Delete')) }}"
                                    onclick="confirmFormSubmit(this.closest('form'), { title: this.dataset.confirmTitle, message: this.dataset.confirmMessage, confirmText: this.dataset.confirmText, type: 'danger' })"
                                >Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No applications found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($applications->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">
            {{ $applications->links() }}
        </div>
    @endif
</div>
@endsection
