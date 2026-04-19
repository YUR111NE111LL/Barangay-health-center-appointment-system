@extends('tenant.layouts.app')

@section('title', 'Add medicine')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Add medicine</h1>
    <p class="mt-1 text-slate-500">Add a photo and starting stock. Assign “manage medicine” in Role permissions so staff can access this tab.</p>
</div>

<form action="{{ route('backend.medicines.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        @include('tenant.medicines._form')
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Save</button>
        <a href="{{ route('backend.medicines.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
