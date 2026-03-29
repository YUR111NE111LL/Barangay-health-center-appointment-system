@extends('backend.layouts.app')

@section('title', 'Edit Appointment')

@section('content')
<h1 class="mb-2 text-2xl font-bold text-slate-800">Edit Appointment #{{ $appointment->id }}</h1>
<p class="mb-6 text-slate-500">Patient: {{ $appointment->resident?->name ?? '—' }}</p>

<form action="{{ route('backend.appointments.update', $appointment) }}" method="POST" class="max-w-2xl space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    @csrf
    @method('PUT')
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="scheduled_date" class="mb-1 block text-sm font-medium text-slate-700">Date <span class="text-rose-500">*</span></label>
            <input type="date" name="scheduled_date" id="scheduled_date" value="{{ old('scheduled_date', $appointment->scheduled_date->format('Y-m-d')) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
            @error('scheduled_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="scheduled_time" class="mb-1 block text-sm font-medium text-slate-700">Time <span class="text-rose-500">*</span></label>
            <input type="time" name="scheduled_time" id="scheduled_time" value="{{ old('scheduled_time', \Carbon\Carbon::parse($appointment->scheduled_time)->format('H:i')) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
            @error('scheduled_time')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Status <span class="text-rose-500">*</span></label>
        <select name="status" id="status" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
            @foreach(\App\Models\Appointment::statuses() as $label => $value)
                <option value="{{ $value }}" {{ old('status', $appointment->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="complaint" class="mb-1 block text-sm font-medium text-slate-700">Complaint</label>
        <textarea name="complaint" id="complaint" rows="2" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('complaint', $appointment->complaint) }}</textarea>
        @error('complaint')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Staff notes</label>
        <textarea name="notes" id="notes" rows="2" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('notes', $appointment->notes) }}</textarea>
        @error('notes')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Update</button>
        <a href="{{ route('backend.appointments.show', $appointment) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
