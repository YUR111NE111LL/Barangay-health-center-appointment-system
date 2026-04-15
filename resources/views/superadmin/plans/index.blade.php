@extends('superadmin.layouts.app')

@section('title', 'Plan Pricing')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Plan Pricing</h1>
        <p class="mt-1 text-sm text-slate-600">Update monthly prices for Basic, Standard, and Premium plans.</p>
    </div>
    <a href="{{ route('super-admin.tenants.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Back to Tenants</a>
</div>

@if($errors->any())
    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Plan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Current price</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Update monthly price</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($plans as $plan)
                    <tr>
                        <td class="px-4 py-4">
                            <div class="font-semibold text-slate-800">{{ $plan->name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $plan->pricingSummaryLine() }}</div>
                        </td>
                        <td class="px-4 py-4 text-sm text-slate-700">{{ $plan->formattedPrice() }}</td>
                        <td class="px-4 py-4">
                            <form action="{{ route('super-admin.plans.update', $plan) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PUT')
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">{{ config('bhcas.currency_symbol', '₱') }}</span>
                                    <input
                                        type="number"
                                        name="price"
                                        value="{{ old('price', $plan->price) }}"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="w-40 rounded-xl border-slate-300 bg-slate-50 py-2.5 pl-8 pr-3 text-sm text-slate-800 focus:border-violet-500 focus:ring-violet-500"
                                    >
                                </div>
                                <button type="submit" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-violet-700">
                                    Update price
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-4 text-right text-xs text-slate-500">Monthly</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-slate-500">No plans found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
