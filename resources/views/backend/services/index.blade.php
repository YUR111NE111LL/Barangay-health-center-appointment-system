@extends('backend.layouts.app')

@section('title', __('Appointment services'))

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ __('Appointment services') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('Services appear in the resident Book form. Add or edit what your barangay offers.') }}</p>
    </div>
    <a href="{{ route('backend.services.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 font-medium text-white shadow-sm transition hover:bg-teal-700">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        {{ __('Add service') }}
    </a>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Duration') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Order') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($services as $service)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $service->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $service->duration_minutes }} {{ __('min') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $service->sort_order }}</td>
                        <td class="px-4 py-3">
                            @if($service->is_active)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">{{ __('Active') }}</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-800">{{ __('Hidden') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <a href="{{ route('backend.services.edit', $service) }}" class="font-medium text-teal-600 hover:text-teal-800">{{ __('Edit') }}</a>
                            <form action="{{ route('backend.services.destroy', $service) }}" method="POST" class="inline ml-3">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="button"
                                    class="font-medium text-rose-600 hover:text-rose-800"
                                    data-confirm-title="{{ e(__('Remove service')) }}"
                                    data-confirm-message="{{ e(__('Delete this service? It cannot be removed if appointments already use it.')) }}"
                                    data-confirm-text="{{ e(__('Delete')) }}"
                                    onclick="confirmFormSubmit(this.closest('form'), { title: this.dataset.confirmTitle, message: this.dataset.confirmMessage, confirmText: this.dataset.confirmText, type: 'danger' })"
                                >{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">
                            {{ __('No services yet. Add at least one so residents can book.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($services->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">
            {{ $services->links() }}
        </div>
    @endif
</div>
@endsection
