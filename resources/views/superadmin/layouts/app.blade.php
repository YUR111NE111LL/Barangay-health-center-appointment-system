<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Super Admin') – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased" data-session-portal="{{ $sessionPortalKey ?? 'public' }}" @if(auth()->check()) data-current-user-id="{{ auth()->id() }}" @endif>
    <nav class="sticky top-0 z-50 border-b border-violet-900/20 bg-linear-to-r from-violet-800 to-violet-900 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex min-h-14 w-full flex-wrap items-center gap-y-2 py-2 lg:flex-nowrap lg:gap-3 lg:py-0">
                <div class="flex min-w-0 shrink-0 items-center gap-2">
                    <button type="button" id="sa-mobile-menu-btn" class="rounded-lg p-1.5 text-white/90 hover:bg-white/10 lg:hidden" aria-label="Menu">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('super-admin.dashboard') }}" class="truncate font-semibold text-white">{{ config('bhcas.acronym') }} – Super Admin</a>
                </div>
                @php
                    $saDefault = 'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium text-white/80 transition hover:bg-white/15 hover:text-white';
                    $saActive  = 'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-violet-800 shadow-sm';
                @endphp
                <div class="hidden min-w-0 flex-1 lg:flex lg:justify-center">
                <div id="sa-nav-links" class="flex max-w-full flex-nowrap items-center gap-1 overflow-x-auto overflow-y-visible py-1 [scrollbar-color:rgba(255,255,255,0.35)_transparent] [scrollbar-width:thin] [&::-webkit-scrollbar]:h-1 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-white/30">
                    <a href="{{ route('super-admin.dashboard') }}" class="{{ request()->routeIs('super-admin.dashboard') ? $saActive : $saDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('super-admin.tenants.index') }}" class="{{ request()->routeIs('super-admin.tenants.*', 'super-admin.tenant-audit-logs.index') ? $saActive : $saDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Tenants
                    </a>
                    <a href="{{ route('super-admin.plans.index') }}" class="{{ request()->routeIs('super-admin.plans.*') ? $saActive : $saDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Plans
                    </a>
                    <a href="{{ route('super-admin.tenant-applications.index') }}" class="relative {{ request()->routeIs('super-admin.tenant-applications.*') ? $saActive : $saDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Tenant requests
                        @if(($tenantApplicationPendingCount ?? 0) > 0)<span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-violet-900">{{ $tenantApplicationPendingCount }}</span>@endif
                    </a>
                    <a href="{{ route('super-admin.accounts.index') }}" class="{{ request()->routeIs('super-admin.accounts.*') ? $saActive : $saDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Accounts
                    </a>
                    <a href="{{ route('super-admin.users.index') }}" class="{{ request()->routeIs('super-admin.users.*') ? $saActive : $saDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Users
                    </a>
                    <a href="{{ route('super-admin.support-reports.index') }}" class="relative {{ request()->routeIs('super-admin.support-reports.*') ? $saActive : $saDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Tenants reports
                        @if(($supportReportPendingCount ?? 0) > 0)<span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-violet-900">{{ $supportReportPendingCount }}</span>@endif
                    </a>
                    <a href="{{ route('super-admin.updates.index') }}" class="{{ request()->routeIs('super-admin.updates.*') ? $saActive : $saDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        Updates
                    </a>
                    <a href="{{ route('super-admin.pending-approvals.index') }}" class="relative {{ request()->routeIs('super-admin.pending-approvals.*') ? $saActive : $saDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Pending approvals
                        @if(($pendingCount ?? 0) > 0)<span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-violet-900">{{ $pendingCount }}</span>@endif
                    </a>
                </div>
                </div>
                <details class="group relative ml-auto shrink-0" id="sa-user-menu">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full px-2 py-1.5 text-white/90 hover:bg-white/10 [&::-webkit-details-marker]:hidden">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs font-semibold text-white" aria-hidden="true">{{ auth()->user()->initials }}</span>
                        <span class="hidden max-w-[9rem] truncate text-left text-sm sm:inline">{{ auth()->user()->name }}</span>
                        <svg class="h-4 w-4 shrink-0 text-white/70 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="absolute right-0 z-[60] mt-1.5 min-w-[14rem] max-w-[min(18rem,calc(100vw-2rem))] rounded-xl border border-white/10 bg-violet-950 py-1 shadow-xl ring-1 ring-black/20">
                        <div class="border-b border-white/10 px-3 py-2.5">
                            <p class="break-words text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                            @if(auth()->user()->email)
                                <p class="mt-0.5 break-all text-xs text-white/60">{{ auth()->user()->email }}</p>
                            @endif
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="px-1 py-1">
                            @csrf
                            <input type="hidden" name="session_portal" value="{{ $sessionPortalKey ?? 'public' }}">
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-white/90 hover:bg-white/10">
                                <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </details>
            </div>

            {{-- Mobile menu --}}
            @php
                $saMobDefault = 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-white/80 hover:bg-white/10';
                $saMobActive  = 'inline-flex items-center gap-2 rounded-lg bg-white/25 px-3 py-2 text-sm font-semibold text-white border-l-4 border-white';
            @endphp
            <div id="sa-mobile-menu" class="hidden border-t border-white/20 pb-3 pt-2 lg:hidden">
                <div class="flex flex-col gap-0.5">
                    <a href="{{ route('super-admin.dashboard') }}" class="{{ request()->routeIs('super-admin.dashboard') ? $saMobActive : $saMobDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('super-admin.tenants.index') }}" class="{{ request()->routeIs('super-admin.tenants.*', 'super-admin.tenant-audit-logs.index') ? $saMobActive : $saMobDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Tenants
                    </a>
                    <a href="{{ route('super-admin.plans.index') }}" class="{{ request()->routeIs('super-admin.plans.*') ? $saMobActive : $saMobDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Plans
                    </a>
                    <a href="{{ route('super-admin.tenant-applications.index') }}" class="{{ request()->routeIs('super-admin.tenant-applications.*') ? $saMobActive : $saMobDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Tenant requests @if(($tenantApplicationPendingCount ?? 0) > 0)<span class="ml-1 rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-semibold text-violet-900">{{ $tenantApplicationPendingCount }}</span>@endif
                    </a>
                    <a href="{{ route('super-admin.accounts.index') }}" class="{{ request()->routeIs('super-admin.accounts.*') ? $saMobActive : $saMobDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Accounts
                    </a>
                    <a href="{{ route('super-admin.users.index') }}" class="{{ request()->routeIs('super-admin.users.*') ? $saMobActive : $saMobDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Users
                    </a>
                    <a href="{{ route('super-admin.support-reports.index') }}" class="{{ request()->routeIs('super-admin.support-reports.*') ? $saMobActive : $saMobDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Tenants reports @if(($supportReportPendingCount ?? 0) > 0)<span class="ml-1 rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-semibold text-violet-900">{{ $supportReportPendingCount }}</span>@endif
                    </a>
                    <a href="{{ route('super-admin.updates.index') }}" class="{{ request()->routeIs('super-admin.updates.*') ? $saMobActive : $saMobDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        Updates
                    </a>
                    <a href="{{ route('super-admin.pending-approvals.index') }}" class="{{ request()->routeIs('super-admin.pending-approvals.*') ? $saMobActive : $saMobDefault }}">
                        <svg class="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Pending approvals @if(($pendingCount ?? 0) > 0)<span class="ml-1 rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-semibold text-violet-900">{{ $pendingCount }}</span>@endif
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 text-emerald-800 ring-1 ring-emerald-200">
                <span>{{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="rounded p-1 hover:bg-emerald-100" aria-label="Dismiss">&times;</button>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 flex items-center justify-between rounded-xl bg-rose-50 px-4 py-3 text-rose-800 ring-1 ring-rose-200">
                <span>{{ session('error') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="rounded p-1 hover:bg-rose-100" aria-label="Dismiss">&times;</button>
            </div>
        @endif
        @if(session('info'))
            <div class="mb-4 flex items-center justify-between rounded-xl bg-sky-50 px-4 py-3 text-sky-900 ring-1 ring-sky-200">
                <span>{{ session('info') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="rounded p-1 hover:bg-sky-100" aria-label="Dismiss">&times;</button>
            </div>
        @endif
        @yield('content')
    </main>

    <script>
    (function() {
        var mobileBtn = document.getElementById('sa-mobile-menu-btn');
        var mobileMenu = document.getElementById('sa-mobile-menu');
        if (mobileBtn && mobileMenu) {
            mobileBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    })();
    </script>
    @include('components.professional-alerts')
    @include('components.session-tab-sync')
    @stack('scripts')
</body>
</html>
