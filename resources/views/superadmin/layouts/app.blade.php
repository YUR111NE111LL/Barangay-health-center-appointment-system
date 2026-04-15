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
            <div class="flex h-14 items-center justify-between">
                <div class="flex items-center gap-2">
                    <button type="button" id="sa-mobile-menu-btn" class="rounded-lg p-1.5 text-white/90 hover:bg-white/10 lg:hidden" aria-label="Menu">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('super-admin.dashboard') }}" class="font-semibold text-white">{{ config('bhcas.acronym') }} – Super Admin</a>
                </div>
                @php
                    $saDefault = 'whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium text-white/80 transition hover:bg-white/15 hover:text-white';
                    $saActive  = 'whitespace-nowrap rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-violet-800 shadow-sm';
                @endphp
                <div id="sa-nav-links" class="hidden items-center gap-1 lg:flex">
                    <a href="{{ route('super-admin.dashboard') }}" class="{{ request()->routeIs('super-admin.dashboard') ? $saActive : $saDefault }}">Dashboard</a>
                    <a href="{{ route('super-admin.tenants.index') }}" class="{{ request()->routeIs('super-admin.tenants.*') ? $saActive : $saDefault }}">Tenants</a>
                    <a href="{{ route('super-admin.plans.index') }}" class="{{ request()->routeIs('super-admin.plans.*') ? $saActive : $saDefault }}">Plans</a>
                    <a href="{{ route('super-admin.tenant-applications.index') }}" class="relative {{ request()->routeIs('super-admin.tenant-applications.*') ? $saActive : $saDefault }}">
                        Tenant requests
                        @if(($tenantApplicationPendingCount ?? 0) > 0)<span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-violet-900">{{ $tenantApplicationPendingCount }}</span>@endif
                    </a>
                    <a href="{{ route('super-admin.accounts.index') }}" class="{{ request()->routeIs('super-admin.accounts.*') ? $saActive : $saDefault }}">Accounts</a>
                    <a href="{{ route('super-admin.users.index') }}" class="{{ request()->routeIs('super-admin.users.*') ? $saActive : $saDefault }}">Users</a>
                    <a href="{{ route('super-admin.support-reports.index') }}" class="relative {{ request()->routeIs('super-admin.support-reports.*') ? $saActive : $saDefault }}">
                        Tenants reports
                        @if(($supportReportPendingCount ?? 0) > 0)<span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-violet-900">{{ $supportReportPendingCount }}</span>@endif
                    </a>
                    <a href="{{ route('super-admin.pending-approvals.index') }}" class="relative {{ request()->routeIs('super-admin.pending-approvals.*') ? $saActive : $saDefault }}">
                        Pending approvals
                        @if(($pendingCount ?? 0) > 0)<span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-violet-900">{{ $pendingCount }}</span>@endif
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-white/80 sm:inline">{{ auth()->user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="session_portal" value="{{ $sessionPortalKey ?? 'public' }}">
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-white/90 hover:bg-white/10 hover:text-white">Logout</button>
                    </form>
                </div>
            </div>

            {{-- Mobile menu --}}
            @php
                $saMobDefault = 'rounded-lg px-3 py-2 text-sm text-white/80 hover:bg-white/10';
                $saMobActive  = 'rounded-lg bg-white/25 px-3 py-2 text-sm font-semibold text-white border-l-4 border-white';
            @endphp
            <div id="sa-mobile-menu" class="hidden border-t border-white/20 pb-3 pt-2 lg:hidden">
                <div class="flex flex-col gap-0.5">
                    <a href="{{ route('super-admin.dashboard') }}" class="{{ request()->routeIs('super-admin.dashboard') ? $saMobActive : $saMobDefault }}">Dashboard</a>
                    <a href="{{ route('super-admin.tenants.index') }}" class="{{ request()->routeIs('super-admin.tenants.*') ? $saMobActive : $saMobDefault }}">Tenants</a>
                    <a href="{{ route('super-admin.plans.index') }}" class="{{ request()->routeIs('super-admin.plans.*') ? $saMobActive : $saMobDefault }}">Plans</a>
                    <a href="{{ route('super-admin.tenant-applications.index') }}" class="{{ request()->routeIs('super-admin.tenant-applications.*') ? $saMobActive : $saMobDefault }}">Tenant requests @if(($tenantApplicationPendingCount ?? 0) > 0)<span class="ml-1 rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-semibold text-violet-900">{{ $tenantApplicationPendingCount }}</span>@endif</a>
                    <a href="{{ route('super-admin.accounts.index') }}" class="{{ request()->routeIs('super-admin.accounts.*') ? $saMobActive : $saMobDefault }}">Accounts</a>
                    <a href="{{ route('super-admin.users.index') }}" class="{{ request()->routeIs('super-admin.users.*') ? $saMobActive : $saMobDefault }}">Users</a>
                    <a href="{{ route('super-admin.support-reports.index') }}" class="{{ request()->routeIs('super-admin.support-reports.*') ? $saMobActive : $saMobDefault }}">Tenants reports @if(($supportReportPendingCount ?? 0) > 0)<span class="ml-1 rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-semibold text-violet-900">{{ $supportReportPendingCount }}</span>@endif</a>
                    <a href="{{ route('super-admin.pending-approvals.index') }}" class="{{ request()->routeIs('super-admin.pending-approvals.*') ? $saMobActive : $saMobDefault }}">Pending approvals @if(($pendingCount ?? 0) > 0)<span class="ml-1 rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-semibold text-violet-900">{{ $pendingCount }}</span>@endif</a>
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
