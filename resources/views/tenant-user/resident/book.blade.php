@extends('tenant-user.layouts.app')

@section('title', 'Book Appointment')

@section('content')
<h1 class="mb-2 text-2xl font-bold text-slate-800">Book an Appointment</h1>
<p class="mb-6 text-slate-500">{{ $tenant->barangayDisplayName() }}</p>

@if($services->isEmpty())
    <div class="max-w-xl rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
        <p class="font-medium">{{ __('No services are available yet.') }}</p>
        <p class="mt-1 text-amber-800/90">{{ __('Your barangay health center has not added appointment services, or they are all hidden. Please contact your health center admin.') }}</p>
    </div>
@else
<form action="{{ route('resident.book.store') }}" method="POST" class="max-w-xl space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 sm:p-8">
    @csrf
    <div>
        <label for="service_id" class="mb-1 block text-sm font-medium text-slate-700">Service <span class="text-rose-500">*</span></label>
        <select name="service_id" id="service_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 focus:border-teal-500 focus:ring-teal-500" required>
            <option value="">Select service...</option>
            @foreach($services as $s)
                <option value="{{ $s->id }}" {{ old('service_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->duration_minutes }} min)</option>
            @endforeach
        </select>
        @error('service_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="scheduled_date" class="mb-1 block text-sm font-medium text-slate-700">Date <span class="text-rose-500">*</span></label>
            <input type="date" name="scheduled_date" id="scheduled_date" value="{{ old('scheduled_date') }}" min="{{ date('Y-m-d') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
            @error('scheduled_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="scheduled_time" class="mb-1 block text-sm font-medium text-slate-700">Time <span class="text-rose-500">*</span></label>
            <input type="time" name="scheduled_time" id="scheduled_time" value="{{ old('scheduled_time') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
            @error('scheduled_time')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label for="complaint" class="mb-1 block text-sm font-medium text-slate-700">Complaint / Reason (optional)</label>
        <textarea name="complaint" id="complaint" rows="2" placeholder="Brief reason for visit" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('complaint') }}</textarea>
        @error('complaint')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="flex flex-wrap gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">Submit Request</button>
        <a href="{{ route('resident.dashboard') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endif
@endsection
