@extends('tenant.layouts.app')

@section('title', 'Appointments')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Appointments</h1>
    @if(auth()->user()->hasTenantPermission('encode appointments'))
    <a href="{{ route('backend.appointments.create') }}" class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-teal-700">New Appointment</a>
    @endif
</div>

<form class="mb-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60" method="GET">
    <div class="min-w-[140px]">
        <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
        <select name="status" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">All statuses</option>
            @foreach(\App\Models\Appointment::statuses() as $label => $value)
                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-[140px]">
        <label class="mb-1 block text-xs font-medium text-slate-500">Date</label>
        <input type="date" name="date" value="{{ request('date') }}" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
    </div>
    <button type="submit" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Filter</button>
</form>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Service</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($appointments as $apt)
                <tr class="hover:bg-slate-50/50">
                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $apt->scheduled_date->format('M d, Y') }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ \Carbon\Carbon::parse($apt->scheduled_time)->format('g:i A') }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $apt->resident?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $apt->service?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-3">
                        @if($apt->status === 'approved')
                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">approved</span>
                        @elseif($apt->status === 'pending')
                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">pending</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $apt->status }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                        @if(auth()->user()->hasTenantPermission('approve appointments') && $apt->status === 'pending')
                            <form action="{{ route('backend.appointments.approve', $apt) }}" method="POST" class="mr-2 inline">
                                @csrf
                                <button type="submit" class="font-medium text-emerald-600 hover:text-emerald-800">{{ __('Approve') }}</button>
                            </form>
                            <form action="{{ route('backend.appointments.reject', $apt) }}" method="POST" class="mr-2 inline">
                                @csrf
                                <button
                                    type="button"
                                    class="font-medium text-rose-600 hover:text-rose-800"
                                    data-confirm-title="{{ e(__('Reject appointment request')) }}"
                                    data-confirm-message="{{ e(__('The resident will be notified by email if enabled. Continue?')) }}"
                                    data-confirm-text="{{ e(__('Reject')) }}"
                                    onclick="confirmFormSubmit(this.closest('form'), { title: this.dataset.confirmTitle, message: this.dataset.confirmMessage, confirmText: this.dataset.confirmText, type: 'danger' })"
                                >{{ __('Reject') }}</button>
                            </form>
                        @endif
                        <a href="{{ route('backend.appointments.show', $apt) }}" class="font-medium text-teal-600 hover:text-teal-700">{{ __('View') }}</a>
                        @if(auth()->user()->hasTenantPermission('encode appointments'))
                            <span class="mx-2 text-slate-300" aria-hidden="true">|</span>
                            <form action="{{ route('backend.appointments.destroy', $apt) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="button"
                                    class="font-medium text-rose-600 hover:text-rose-800"
                                    data-confirm-title="{{ e(__('Delete appointment')) }}"
                                    data-confirm-message="{{ e(__('This will permanently remove this appointment from the list. This cannot be undone.')) }}"
                                    data-confirm-text="{{ e(__('Delete')) }}"
                                    onclick="confirmFormSubmit(this.closest('form'), { title: this.dataset.confirmTitle, message: this.dataset.confirmMessage, confirmText: this.dataset.confirmText, type: 'danger' })"
                                >{{ __('Delete') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No appointments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4 flex justify-center">
    {{ $appointments->withQueryString()->links() }}
</div>
@endsection
