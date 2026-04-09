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
    <div class="mb-6">
        <a href="{{ route('backend.users.google') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Create with Google Account
        </a>
    </div>

    <div class="relative my-6">
        <span class="relative flex justify-center text-xs text-slate-400"><span class="bg-white px-2">OR</span></span>
    </div>

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
