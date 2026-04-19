@extends('tenant.layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Inventory</h1>
    <p class="mt-1 text-slate-500">Medicine acquisitions from residents (stock is reduced when they acquire). Pricing is set per medicine when staff add or edit a listing — choose <strong>free</strong> or a <strong>price per unit</strong>.</p>
</div>

@if(!empty($acquisitionsTableMissing))
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-medium">Acquisition history is not available yet.</p>
        <p class="mt-1 text-amber-800">Run tenant migrations (e.g. <code class="rounded bg-amber-100 px-1 py-0.5 text-xs">php artisan tenants:migrate</code>) so the <code class="rounded bg-white px-1 ring-1 ring-amber-200">medicine_acquisitions</code> table exists.</p>
    </div>
@elseif($acquisitions !== null)
    @php $sym = config('bhcas.currency_symbol', '₱'); @endphp
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Acquisition events</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ (int) ($totals->acquisition_count ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Units distributed</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ (int) ($totals->units_total ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total at priced acquisitions</p>
            <p class="mt-1 text-2xl font-bold text-teal-800">{{ $sym }}{{ number_format((float) ($totals->amount_total ?? 0), 2) }}</p>
            <p class="mt-1 text-xs text-slate-500">Free supplies count as {{ $sym }}0.00 here.</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="font-semibold text-slate-900">All medicine acquisitions</h2>
            <p class="mt-0.5 text-sm text-slate-500">Every time a resident confirms an acquire request, it appears here with amount due (if priced).</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3">When</th>
                        <th class="whitespace-nowrap px-4 py-3">Resident</th>
                        <th class="px-4 py-3">Medicine</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">Qty</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">Unit price</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">Line total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($acquisitions as $row)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ \App\Support\DateDisplay::format($row->created_at, 'Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">
                                @if($row->user)
                                    <span class="font-medium text-slate-800">{{ $row->user->name }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $row->user->email }}</span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-800">{{ $row->medicine?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-800">{{ $row->quantity }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                @if($row->is_free)
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">Free</span>
                                @else
                                    {{ $sym }}{{ number_format((float) $row->unit_price_snapshot, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">
                                @if($row->is_free)
                                    —
                                @else
                                    {{ $sym }}{{ number_format((float) $row->line_total, 2) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500">No acquisitions yet. When residents acquire posted medicine, rows will appear here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 flex justify-center">
        {{ $acquisitions->withQueryString()->links() }}
    </div>
@endif
@endsection
