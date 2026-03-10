@extends('backend.layouts.app')

@section('title', 'New Appointment')

@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="mb-6 text-2xl font-bold text-slate-800">New Appointment</h1>
    <form action="{{ route('backend.appointments.store') }}" method="POST" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 sm:p-8">
        @csrf
        <div>
            <label for="user_id" class="mb-1 block text-sm font-medium text-slate-700">Patient (Resident) <span class="text-rose-500">*</span></label>
            <select name="user_id" id="user_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
                <option value="">Select resident...</option>
                @foreach($residents as $u)
                    <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
            @error('user_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="service_id" class="mb-1 block text-sm font-medium text-slate-700">Service <span class="text-rose-500">*</span></label>
            <select name="service_id" id="service_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
                <option value="">Select service...</option>
                @foreach($services as $s)
                    <option value="{{ $s->id }}" {{ old('service_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('service_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="scheduled_date" class="mb-1 block text-sm font-medium text-slate-700">Date <span class="text-rose-500">*</span></label>
                <input type="date" name="scheduled_date" id="scheduled_date" value="{{ old('scheduled_date') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
                @error('scheduled_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="scheduled_time" class="mb-1 block text-sm font-medium text-slate-700">Time <span class="text-rose-500">*</span></label>
                <input type="time" name="scheduled_time" id="scheduled_time" value="{{ old('scheduled_time') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
                @error('scheduled_time')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label for="complaint" class="mb-1 block text-sm font-medium text-slate-700">Complaint / Reason</label>
            <textarea name="complaint" id="complaint" rows="2" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('complaint') }}</textarea>
            @error('complaint')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">Create Appointment</button>
            <a href="{{ route('backend.appointments.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
