@extends('frontend.layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-800">My Profile</h1>
    <a href="{{ route('resident.profile.edit') }}" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Edit profile
    </a>
</div>

<div class="space-y-6">
    <!-- Profile Header Card -->
    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-cyan-600 shadow-lg ring-1 ring-slate-200/60">
        <div class="p-6 sm:p-8">
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                <!-- Profile Picture -->
                <div class="relative flex-shrink-0">
                    @if($user->profile_picture)
                        <img src="{{ str_contains($user->profile_picture, 'cloudinary.com') ? $user->profile_picture : asset('storage/' . $user->profile_picture) }}" 
                             alt="{{ $user->name }}" 
                             class="h-20 w-20 rounded-full border-3 border-white/30 object-cover object-center shadow-xl ring-2 ring-white/20 sm:h-24 sm:w-24">
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-full border-3 border-white/30 bg-white/20 text-2xl font-bold text-white shadow-xl ring-2 ring-white/20 backdrop-blur-sm sm:h-24 sm:w-24 sm:text-3xl">
                            {{ $user->initials }}
                        </div>
                    @endif
                    <div class="absolute -bottom-0.5 -right-0.5 h-5 w-5 rounded-full border-2 border-white bg-emerald-500 shadow-md"></div>
                </div>
                
                <!-- User Info -->
                <div class="flex-1 text-center sm:text-left">
                    <h2 class="text-2xl font-bold text-white sm:text-3xl">{{ $user->name }}</h2>
                    <p class="mt-1 text-sm text-white/90 sm:text-base">{{ $user->email }}</p>
                    @if($user->purok_address)
                        <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm sm:text-sm">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $user->purok_address }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Account Details Card -->
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100/50 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-800">Account Information</h3>
            <p class="mt-0.5 text-xs text-slate-500">Your account details and settings</p>
        </div>
        <dl class="divide-y divide-slate-100">
            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="flex items-center gap-2 text-sm font-medium text-slate-500">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Name
                </dt>
                <dd class="mt-1 text-sm font-medium text-slate-900 sm:col-span-2 sm:mt-0">{{ $user->name }}</dd>
            </div>
            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="flex items-center gap-2 text-sm font-medium text-slate-500">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Email
                </dt>
                <dd class="mt-1 text-sm text-slate-800 sm:col-span-2 sm:mt-0">{{ $user->email }}</dd>
            </div>
            @if($user->purok_address)
            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="flex items-center gap-2 text-sm font-medium text-slate-500">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Purok Address
                </dt>
                <dd class="mt-1 text-sm text-slate-800 sm:col-span-2 sm:mt-0">{{ $user->purok_address }}</dd>
            </div>
            @endif
            @if($user->tenant)
            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="flex items-center gap-2 text-sm font-medium text-slate-500">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Barangay / Health center
                </dt>
                <dd class="mt-1 text-sm text-slate-800 sm:col-span-2 sm:mt-0">{{ $user->tenant->getDisplayName() }}</dd>
            </div>
            @endif
            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="flex items-center gap-2 text-sm font-medium text-slate-500">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Role
                </dt>
                <dd class="mt-1 text-sm text-slate-800 sm:col-span-2 sm:mt-0">
                    <span class="inline-flex items-center rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-800">{{ $user->role }}</span>
                </dd>
            </div>
        </dl>
    </div>
</div>
@endsection
