@extends('tenant.layouts.app')

@section('title', 'Add user')

@section('content')
<h1 class="mb-2 text-2xl font-bold text-slate-800">Add user</h1>
<p class="mb-6 text-slate-500">Create an account for a resident, staff, nurse, or admin. You can create manually or use their Google account.</p>

@if(session('info'))
    <div class="mb-4 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-700 ring-1 ring-blue-200">
        {{ session('info') }}
    </div>
@endif

@if(session('google_user_data'))
    @php
        $googleData = session('google_user_data');
    @endphp
    <div class="mb-6 rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-200">
        <p class="mb-3 text-sm font-medium text-emerald-800">Google Account Selected:</p>
        <p class="text-sm text-emerald-700"><strong>Name:</strong> {{ $googleData['name'] }}</p>
        <p class="text-sm text-emerald-700"><strong>Email:</strong> {{ $googleData['email'] }}</p>
    </div>
    
    <form action="{{ route('backend.users.store.google') }}" method="POST" class="max-w-xl space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        @csrf
        <input type="hidden" name="name" value="{{ $googleData['name'] }}">
        <input type="hidden" name="email" value="{{ $googleData['email'] }}">
        <input type="hidden" name="google_id" value="{{ $googleData['google_id'] }}">
        
        <div>
            <label for="role" class="mb-1 block text-sm font-medium text-slate-700">Role <span class="text-rose-500">*</span></label>
            <select name="role" id="role" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('role')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Create user with Google account</button>
            <a href="{{ route('backend.users.create') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
@else
    <form action="{{ route('backend.users.store') }}" method="POST" class="max-w-xl space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        @csrf
        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Full name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
            @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email <span class="text-rose-500">*</span></label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password <span class="text-rose-500">*</span></label>
            <input type="password" name="password" id="password" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required minlength="8">
            @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password <span class="text-rose-500">*</span></label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
        </div>
        <div>
            <label for="role" class="mb-1 block text-sm font-medium text-slate-700">Role <span class="text-rose-500">*</span></label>
            <select name="role" id="role" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('role')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Create user</button>
            <a href="{{ route('backend.users.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
@endif
@endsection
