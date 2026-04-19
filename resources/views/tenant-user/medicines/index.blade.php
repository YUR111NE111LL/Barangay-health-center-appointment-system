@extends('tenant-user.layouts.app')

@section('title', 'Medicine')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Medicine</h1>
    <p class="mt-1 text-slate-500">Available supplies from your barangay health center. Acquiring reduces stock for everyone.</p>
</div>

@if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($medicines as $m)
    <div class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="aspect-[4/3] bg-slate-100">
            @if($m->image_path)
                <img src="{{ $m->image_url }}" alt="" class="h-full w-full object-cover" />
            @else
                <div class="flex h-full w-full items-center justify-center text-sm text-slate-400">No image</div>
            @endif
        </div>
        <div class="flex flex-1 flex-col p-4">
            <h2 class="font-semibold text-slate-800">{{ $m->name }}</h2>
            @if($m->description)
                <p class="mt-1 flex-1 text-sm text-slate-600 line-clamp-3">{{ $m->description }}</p>
            @endif
            <div class="mt-3">
                @if($m->isOutOfStock())
                    <p class="text-sm font-medium text-rose-600">Out of stock</p>
                @else
                    <p class="text-sm text-slate-600">{{ $m->quantity }} available</p>
                    @if($m->isPricedSupply())
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ config('bhcas.currency_symbol', '₱') }}{{ number_format((float) $m->price_per_unit, 2) }} per unit</p>
                    @else
                        <p class="mt-1 text-xs font-medium text-emerald-700">Free of charge</p>
                    @endif
                    @can('acquire medicine')
                    <form action="{{ route('resident.medicine.acquire', $m) }}" method="POST" class="mt-3 flex flex-wrap items-end gap-2">
                        @csrf
                        <div>
                            <label for="qty-{{ $m->id }}" class="sr-only">Quantity</label>
                            <input type="number" name="quantity" id="qty-{{ $m->id }}" value="1" min="1" max="{{ min(100, $m->quantity) }}" class="w-20 rounded-lg border-slate-300 bg-slate-50 px-2 py-1.5 text-sm focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <button type="submit" class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-teal-700">Acquire</button>
                    </form>
                    @else
                    <p class="mt-2 text-xs text-slate-500">You can see availability here. Your role may need the &ldquo;acquire medicine&rdquo; permission to request supplies—ask your Health Center Admin.</p>
                    @endcan
                @endif
            </div>
        </div>
    </div>
    @empty
    <p class="col-span-full rounded-2xl bg-white p-8 text-center text-slate-500 shadow-sm ring-1 ring-slate-200/60">No medicines posted yet.</p>
    @endforelse
</div>

<div class="mt-6 flex justify-center">{{ $medicines->links() }}</div>
@endsection
