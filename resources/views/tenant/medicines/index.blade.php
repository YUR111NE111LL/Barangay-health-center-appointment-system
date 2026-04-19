@extends('tenant.layouts.app')

@section('title', 'Medicine')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Medicine</h1>
        <p class="mt-1 text-slate-500">Post medicines with photos, stock counts, and <strong>free or priced</strong> supply. Residents&rsquo; acquisitions are listed below; the full ledger is on <strong>Inventory</strong>. Barangay administrators can also use the <strong>Audit log</strong>.</p>
        <p class="mt-2 text-sm text-slate-600"><strong>How to post:</strong> click <strong>Add medicine</strong> (below), fill in details and optional photo, then save. Accounts need the <strong>Post &amp; manage medicine</strong> permission
            @if(auth()->user()?->hasTenantBarangayAdministrationAccess())
                — assign it under <a href="{{ route('backend.rbac.permissions.index') }}" class="font-medium text-teal-600 hover:text-teal-700">Role permissions</a>.
            @else
                (your Health Center Admin can grant this in Role permissions).
            @endif
            This barangay&rsquo;s plan must also include <strong>Inventory</strong> tracking.</p>
    </div>
    <a href="{{ route('backend.medicines.create') }}" class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-teal-700">Add medicine</a>
</div>

@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Image</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Stock</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Pricing</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($medicines as $m)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-4 py-3">
                        @if($m->image_path)
                            <img src="{{ $m->image_url }}" alt="" class="h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200/80" width="48" height="48" />
                        @else
                            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">No image</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-800">{{ $m->name }}</p>
                        @if($m->description)<p class="mt-1 max-w-md text-xs text-slate-500 line-clamp-2">{{ $m->description }}</p>@endif
                    </td>
                    <td class="px-4 py-3">
                        @if($m->isOutOfStock())
                            <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800">Out of stock</span>
                        @else
                            <span class="text-sm font-medium text-slate-700">{{ $m->quantity }} in stock</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">
                        @if($m->isPricedSupply())
                            <span class="font-medium">{{ config('bhcas.currency_symbol', '₱') }}{{ number_format((float) $m->price_per_unit, 2) }}</span>
                            <span class="block text-xs text-slate-500">per unit</span>
                        @else
                            <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800">Free</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('backend.medicines.edit', $m) }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Edit</a>
                        <form action="{{ route('backend.medicines.destroy', $m) }}" method="POST" class="inline" onsubmit="return confirm('Remove this medicine?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ml-3 text-sm font-medium text-rose-600 hover:text-rose-700">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No medicines yet. Add one to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4 flex justify-center">{{ $medicines->links() }}</div>

@if(isset($recentAcquisitions) && $recentAcquisitions->isNotEmpty())
<div class="mt-10">
    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Recent resident acquisitions</h2>
            <p class="mt-1 text-sm text-slate-500">Who acquired how many units and when (from posted stock).</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @if(auth()->user()?->hasTenantPermission('manage inventory'))
                <a href="{{ route('backend.inventory.index') }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Full inventory ledger →</a>
            @endif
            @if(auth()->user()?->hasTenantBarangayAdministrationAccess())
                <a href="{{ route('backend.audit-log.index') }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Audit log →</a>
            @endif
        </div>
    </div>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">When</th>
                        <th class="px-4 py-3 text-left">Resident</th>
                        <th class="px-4 py-3 text-left">Medicine</th>
                        <th class="px-4 py-3 text-right">Units</th>
                        <th class="px-4 py-3 text-right">Stock after</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($recentAcquisitions as $log)
                        @php
                            $sym = config('bhcas.currency_symbol', '₱');
                        @endphp
                        @if($log instanceof \App\Models\MedicineAcquisition)
                            @php $resident = $log->user; @endphp
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ \App\Support\DateDisplay::format($log->created_at, 'Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if($resident)
                                        <span class="font-medium text-slate-800">{{ $resident->name }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $resident->email }}</span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-800">{{ $log->medicine?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-800">{{ $log->quantity }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">—</td>
                                <td class="px-4 py-3 text-right text-slate-800">{{ $log->is_free ? 'Free' : $sym . number_format((float) $log->line_total, 2) }}</td>
                            </tr>
                        @else
                            @php
                                $nv = is_array($log->new_values) ? $log->new_values : [];
                                $resident = $log->user;
                            @endphp
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ \App\Support\DateDisplay::format($log->created_at, 'Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if($resident)
                                        <span class="font-medium text-slate-800">{{ $resident->name }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $resident->email }}</span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-800">{{ $nv['name'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-800">{{ $nv['quantity_acquired'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $nv['quantity_remaining'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ ($nv['is_free'] ?? true) ? 'Free' : ($sym.($nv['line_total'] ?? '—')) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
