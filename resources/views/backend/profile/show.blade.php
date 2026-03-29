@extends('backend.layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-800">My Profile</h1>
    <a href="{{ route('backend.profile.edit') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Profile
    </a>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    {{-- Profile Header --}}
    <div class="bg-gradient-to-r from-teal-500 to-cyan-600 px-6 py-8">
        <div class="flex flex-col items-center gap-4 sm:flex-row">
            <div class="relative">
                @if($user->profile_picture)
                    <img src="{{ str_contains($user->profile_picture, 'cloudinary.com') ? $user->profile_picture : asset('storage/' . $user->profile_picture) }}"
                         alt="{{ $user->name }}"
                         class="h-20 w-20 rounded-full border-4 border-white/30 object-cover shadow-lg sm:h-24 sm:w-24">
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full border-4 border-white/30 bg-white/20 text-2xl font-bold text-white shadow-lg sm:h-24 sm:w-24 sm:text-3xl">
                        {{ $user->initials }}
                    </div>
                @endif
                <div class="absolute -bottom-0.5 -right-0.5 h-5 w-5 rounded-full border-2 border-white bg-emerald-400 shadow"></div>
            </div>
            <div class="text-center sm:text-left">
                <h2 class="text-xl font-bold text-white sm:text-2xl">{{ $user->name }}</h2>
                <p class="mt-0.5 text-sm text-white/80">{{ $user->email }}</p>
                <span class="mt-2 inline-flex items-center rounded-full bg-white/20 px-3 py-0.5 text-xs font-medium text-white backdrop-blur-sm">
                    {{ $user->role }}
                </span>
            </div>
        </div>
    </div>

    {{-- Profile Details --}}
    <dl class="divide-y divide-slate-100 px-4 sm:px-6">
        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-slate-500">Full Name</dt>
            <dd class="mt-1 text-sm text-slate-800 sm:col-span-2 sm:mt-0">{{ $user->name }}</dd>
        </div>
        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-slate-500">Email Address</dt>
            <dd class="mt-1 text-sm text-slate-800 sm:col-span-2 sm:mt-0">{{ $user->email }}</dd>
        </div>
        @if($user->tenant)
        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-slate-500">Barangay / Tenant</dt>
            <dd class="mt-1 text-sm text-slate-800 sm:col-span-2 sm:mt-0">{{ $user->tenant->barangayDisplayName() }}</dd>
        </div>
        @endif
        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-slate-500">Role</dt>
            <dd class="mt-1 sm:col-span-2 sm:mt-0">
                <span class="inline-flex rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 ring-1 ring-teal-200/60">{{ $user->role }}</span>
            </dd>
        </div>
        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-slate-500">Member Since</dt>
            <dd class="mt-1 text-sm text-slate-800 sm:col-span-2 sm:mt-0">{{ $user->created_at->format('F d, Y') }}</dd>
        </div>
    </dl>
</div>
@endsection
