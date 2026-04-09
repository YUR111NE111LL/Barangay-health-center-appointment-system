@extends('tenant.layouts.app')

@section('title', 'Edit Update')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Edit Update</h1>
    <a href="{{ route('backend.support.updates.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to release notes</a>
</div>

<form action="{{ route('backend.support.updates.update', $note) }}" method="POST" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    @csrf
    @method('PUT')
    @include('tenant.support.updates.partials.form', ['note' => $note])
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Save changes</button>
        <a href="{{ route('backend.support.updates.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
