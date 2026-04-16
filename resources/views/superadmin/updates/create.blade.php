@extends('superadmin.layouts.app')

@section('title', 'Publish Global Update')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Publish Global Update</h1>
    <a href="{{ route('super-admin.updates.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to updates</a>
</div>

<form action="{{ route('super-admin.updates.store') }}" method="POST" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    @csrf
    @include('tenant.support.updates.partials.form')
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-violet-700 px-4 py-2.5 font-medium text-white hover:bg-violet-800">Publish</button>
        <a href="{{ route('super-admin.updates.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
