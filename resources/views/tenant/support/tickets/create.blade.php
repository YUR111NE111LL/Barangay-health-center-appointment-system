@extends('tenant.layouts.app')

@section('title', 'Create Support Ticket')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Create Support Ticket</h1>
    <a href="{{ route($routeBase . '.tickets.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to tickets</a>
</div>

<form action="{{ route($routeBase . '.tickets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="category" class="mb-1 block text-sm font-medium text-slate-700">Category <span class="text-rose-500">*</span></label>
            <select name="category" id="category" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>
                <option value="bug" {{ old('category') === 'bug' ? 'selected' : '' }}>Bug</option>
                <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Account</option>
                <option value="feature_request" {{ old('category') === 'feature_request' ? 'selected' : '' }}>Feature Request</option>
                <option value="general" {{ old('category', 'general') === 'general' ? 'selected' : '' }}>General</option>
            </select>
        </div>
        <div>
            <label for="priority" class="mb-1 block text-sm font-medium text-slate-700">Priority <span class="text-rose-500">*</span></label>
            <select name="priority" id="priority" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>
                @foreach(['low', 'medium', 'high', 'urgent'] as $priority)
                    <option value="{{ $priority }}" {{ old('priority', 'medium') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label for="subject" class="mb-1 block text-sm font-medium text-slate-700">Subject <span class="text-rose-500">*</span></label>
        <input type="text" name="subject" id="subject" value="{{ old('subject') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>
    </div>

    <div>
        <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description <span class="text-rose-500">*</span></label>
        <textarea name="description" id="description" rows="6" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>{{ old('description') }}</textarea>
        <p class="mt-1 text-xs text-slate-500">Please include clear steps and expected result.</p>
    </div>

    <div>
        <label for="attachment" class="mb-1 block text-sm font-medium text-slate-700">Screenshot (optional)</label>
        <input type="file" name="attachment" id="attachment" accept=".png,.jpg,.jpeg,.webp" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5">
        <p class="mt-1 text-xs text-slate-500">Upload a screenshot of the bug (PNG/JPG/WEBP, max 4MB).</p>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Submit ticket</button>
        <a href="{{ route($routeBase . '.tickets.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
