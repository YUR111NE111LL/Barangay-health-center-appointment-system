@extends('tenant.layouts.app')

@section('title', __('Edit service'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">{{ __('Edit service') }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $service->name }}</p>
</div>

<form action="{{ route('backend.services.update', $service) }}" method="POST" class="max-w-xl space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 sm:p-8">
    @csrf
    @method('PUT')
    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Name') }} <span class="text-rose-500">*</span></label>
        <input type="text" name="name" id="name" value="{{ old('name', $service->name) }}" required maxlength="255" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
        @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="description" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Description (optional)') }}</label>
        <textarea name="description" id="description" rows="2" maxlength="2000" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('description', $service->description) }}</textarea>
        @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="duration_minutes" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Duration (minutes)') }} <span class="text-rose-500">*</span></label>
            <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes) }}" min="1" max="480" required class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
            @error('duration_minutes')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="sort_order" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Sort order') }}</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $service->sort_order) }}" min="0" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
            @error('sort_order')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" @checked(old('is_active', $service->is_active))>
        <label for="is_active" class="text-sm text-slate-700">{{ __('Show in resident booking list') }}</label>
    </div>
    <div class="flex flex-wrap gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-teal-700">{{ __('Update') }}</button>
        <a href="{{ route('backend.services.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
