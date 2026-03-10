@extends('backend.layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Inventory</h1>
    <p class="mt-1 text-slate-500">Track supplies and stock for {{ auth()->user()->tenant?->name }}. Available on Premium plan.</p>
</div>

<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    <p class="text-slate-600">Inventory tracking is enabled for your health center. You can add item management and stock levels here in a future update.</p>
</div>
@endsection
