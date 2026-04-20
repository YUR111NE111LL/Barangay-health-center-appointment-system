<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if(config('broadcasting.default') === 'reverb') data-reverb-config="{{ e(json_encode([
    'key' => config('broadcasting.connections.reverb.key'),
    'host' => config('broadcasting.connections.reverb.options.host') ?? 'localhost',
    'port' => (int) (config('broadcasting.connections.reverb.options.port') ?? 8080),
    'scheme' => config('broadcasting.connections.reverb.options.scheme') ?? 'http',
])) }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tenant') – {{ config('bhcas.name') }}</title>
    <script>
        window.reverbConfig = (function() {
            var raw = document.documentElement.getAttribute('data-reverb-config');
            return raw ? JSON.parse(raw) : {};
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @if(!empty($fontUrl))
    <link rel="stylesheet" href="{{ $fontUrl }}">
    @endif
    @if($tenant)
    <link rel="stylesheet" href="{{ route('tenant.custom-css') }}">
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased {{ $themeClass }} @if($navLayout === 'sidebar')layout-sidebar @endif" data-theme="{{ e($tenant?->theme ?? 'default') }}" data-nav-layout="{{ e($navLayout) }}" data-session-portal="{{ $sessionPortalKey ?? 'public' }}" @if($tenant) data-tenant-id="{{ $tenant->id }}" @endif @if(auth()->check()) data-current-user-id="{{ auth()->id() }}" @endif>
    @php
        $isResidentPortal = request()->routeIs('resident.*');
        $dashboardRouteName = $isResidentPortal ? 'resident.dashboard' : 'backend.dashboard';
        $supportRouteName = $isResidentPortal ? 'resident.support.help' : 'backend.support.help';
        $profileRouteName = $isResidentPortal ? 'resident.profile.show' : 'backend.profile.show';
        $dashboardIsActive = $isResidentPortal
            ? request()->routeIs('resident.dashboard')
            : (request()->routeIs('backend.dashboard') || request()->routeIs('backend.admin.dashboard') || request()->routeIs('backend.nurse.dashboard') || request()->routeIs('backend.staff.dashboard'));
    @endphp
    @if($navLayout === 'sidebar')
    <div class="sidebar-overlay" id="backend-sidebar-overlay" aria-hidden="true"></div>
    <!-- Sidebar: drawer on mobile, fixed on md+ -->
    <aside class="tenant-brand-nav sidebar-drawer fixed left-0 top-0 z-40 flex h-full w-56 flex-col border-r border-white/10 bg-teal-600 shadow-lg md:translate-x-0" id="backend-nav" data-brand-color="{{ e($brandColor) }}">
        <div class="flex h-14 items-center justify-between gap-2 border-b border-white/20 px-4">
            <a href="{{ route($dashboardRouteName) }}" class="flex min-w-0 items-center gap-2">
                @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="{{ $brandLogoClass ?? 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25' }}">@endif
                <span class="truncate font-semibold text-white">{{ $brandName }}</span>
            </a>
            <button type="button" id="backend-sidebar-close" class="rounded-lg p-2 text-white/90 hover:bg-white/10 md:hidden" aria-label="Close menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @php
            $sbDefault = 'rounded-lg px-3 py-2.5 text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white transition';
            $sbActive  = 'rounded-lg bg-white/25 px-3 py-2.5 text-sm font-semibold text-white border-l-4 border-white';
        @endphp
        <nav class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto p-3">
            <a href="{{ route($dashboardRouteName) }}" class="{{ $dashboardIsActive ? $sbActive : $sbDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="dashboard" class="h-4 w-4 shrink-0 opacity-90" />Dashboard</a>
            @if(auth()->user()->hasTenantPermission('view appointments'))
            <a href="{{ route('backend.appointments.index') }}" class="{{ request()->routeIs('backend.appointments.*') ? $sbActive : $sbDefault }} {{ ($backendPendingAppointmentsCount ?? 0) > 0 ? 'ring-1 ring-emerald-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center gap-2"><x-tenant-nav-icon name="appointments" class="h-4 w-4 shrink-0 opacity-90" />Appointments @if(($backendPendingAppointmentsCount ?? 0) > 0)<span class="ml-1 rounded-full bg-emerald-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $backendPendingAppointmentsCount }}</span>@endif</a>
            @endif
            @if(auth()->user()->hasTenantBarangayAdministrationAccess())
            <a href="{{ route('backend.services.index') }}" class="{{ request()->routeIs('backend.services.*') ? $sbActive : $sbDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="services" class="h-4 w-4 shrink-0 opacity-90" />Services</a>
            @endif
            @if(auth()->user()->hasTenantPermission('view reports'))
            <a href="{{ route('backend.reports.index') }}" class="{{ request()->routeIs('backend.reports.*') ? $sbActive : $sbDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="reports" class="h-4 w-4 shrink-0 opacity-90" />Reports</a>
            @endif
            @if(!$isResidentPortal)
            <a href="{{ route('backend.users.index') }}" class="{{ request()->routeIs('backend.users.*') ? $sbActive : $sbDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="users" class="h-4 w-4 shrink-0 opacity-90" />Users</a>
            @endif
            <a href="{{ route($supportRouteName) }}" class="{{ request()->routeIs('backend.support.*') || request()->routeIs('resident.support.*') ? $sbActive : $sbDefault }} {{ ($supportUpdatesNotificationCount ?? 0) > 0 ? 'ring-1 ring-emerald-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center gap-2"><x-tenant-nav-icon name="support" class="h-4 w-4 shrink-0 opacity-90" />Support &amp; Updates @if(($supportUpdatesNotificationCount ?? 0) > 0)<span class="ml-1 rounded-full bg-emerald-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $supportUpdatesNotificationCount }}</span>@endif</a>
            @if(auth()->user()->hasTenantBarangayAdministrationAccess())
            <a href="{{ route('backend.pending-approvals.index') }}" class="{{ request()->routeIs('backend.pending-approvals.*') ? $sbActive : $sbDefault }} {{ ($backendPendingCount ?? 0) > 0 ? 'ring-1 ring-amber-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center gap-2"><x-tenant-nav-icon name="approvals" class="h-4 w-4 shrink-0 opacity-90" />Approvals @if(($backendPendingCount ?? 0) > 0)<span class="ml-1 rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $backendPendingCount }}</span>@endif</a>
            <a href="{{ route('backend.announcements.index') }}" class="{{ request()->routeIs('backend.announcements.*') ? $sbActive : $sbDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="announcements" class="h-4 w-4 shrink-0 opacity-90" />Announcements</a>
            <a href="{{ route('backend.events.index') }}" class="{{ request()->routeIs('backend.events.*') ? $sbActive : $sbDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="events" class="h-4 w-4 shrink-0 opacity-90" />Events</a>
            <a href="{{ route('backend.rbac.index') }}" class="{{ request()->routeIs('backend.rbac.*') ? $sbActive : $sbDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="rbac" class="h-4 w-4 shrink-0 opacity-90" />Roles</a>
            <a href="{{ route('backend.audit-log.index') }}" class="{{ request()->routeIs('backend.audit-log.*') ? $sbActive : $sbDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="audit-log" class="h-4 w-4 shrink-0 opacity-90" />Audit log</a>
            @if($hasFeatureWebCustomization ?? false)
            <a href="{{ route('backend.customize-web.edit') }}" class="{{ request()->routeIs('backend.customize-web.*') ? $sbActive : $sbDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="customize" class="h-4 w-4 shrink-0 opacity-90" />Customize</a>
            @endif
            @endif
            @planFeature('inventory')
            @if(auth()->user()->hasTenantPermission('manage inventory'))
            <a href="{{ route('backend.inventory.index') }}" class="{{ request()->routeIs('backend.inventory.*') ? $sbActive : $sbDefault }} {{ ($backendMedicineAcquisitionNotifyCount ?? 0) > 0 ? 'ring-1 ring-emerald-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center gap-2"><x-tenant-nav-icon name="inventory" class="h-4 w-4 shrink-0 opacity-90" />Inventory @if(($backendMedicineAcquisitionNotifyCount ?? 0) > 0)<span class="ml-1 rounded-full bg-emerald-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $backendMedicineAcquisitionNotifyCount }}</span>@endif</a>
            @endif
            @if(auth()->user()->hasTenantPermission('manage medicine'))
            <a href="{{ route('backend.medicines.index') }}" class="{{ request()->routeIs('backend.medicines.*') ? $sbActive : $sbDefault }} {{ ($backendMedicineAcquisitionNotifyCount ?? 0) > 0 ? 'ring-1 ring-emerald-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center gap-2"><x-tenant-nav-icon name="medicine" class="h-4 w-4 shrink-0 opacity-90" />Medicine @if(($backendMedicineAcquisitionNotifyCount ?? 0) > 0)<span class="ml-1 rounded-full bg-emerald-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $backendMedicineAcquisitionNotifyCount }}</span>@endif</a>
            @endif
            @endplanFeature
            <div class="mt-auto border-t border-white/20 pt-3">
                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-white/90 hover:bg-white/10">
                        <span class="max-w-[140px] truncate">{{ auth()->user()->name }}</span>
                        <svg class="h-4 w-4 shrink-0 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <a href="{{ route($profileRouteName) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-white/90 hover:bg-white/10"><x-tenant-nav-icon name="profile" class="h-4 w-4 shrink-0 opacity-90" />Profile</a>
                    <form action="{{ route('logout') }}" method="POST" class="mt-1">
                        @csrf
                        <input type="hidden" name="session_portal" value="{{ $sessionPortalKey ?? 'public' }}">
                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-white/90 hover:bg-white/10">Logout</button>
                    </form>
                </details>
            </div>
        </nav>
    </aside>
    <header class="fixed left-0 right-0 top-0 z-30 flex h-14 items-center justify-between border-b border-white/20 bg-teal-600 px-4 md:hidden" id="backend-sidebar-header" data-brand-color="{{ e($brandColor) }}">
        <button type="button" id="backend-sidebar-open" class="rounded-lg p-2 text-white/90 hover:bg-white/10" aria-label="Open menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="flex min-w-0 items-center justify-center gap-2 font-semibold text-white">
            @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="{{ $brandLogoClass ?? 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25' }}">@endif
            <span class="truncate">{{ $brandName }}</span>
        </span>
        <div class="w-10"></div>
    </header>
    @elseif($navLayout === 'dropdown')
    <!-- Dropdown nav -->
    <nav class="tenant-brand-nav sticky top-0 z-50 border-b border-white/20 bg-teal-600 shadow-sm" id="backend-nav" data-brand-color="{{ e($brandColor) }}">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 min-w-0 items-center justify-between gap-2">
                <a href="{{ route($dashboardRouteName) }}" class="flex min-w-0 shrink-0 items-center gap-2 font-semibold text-white">
                    @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="{{ $brandLogoClass ?? 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25' }}">@endif
                    <span class="truncate">{{ $brandName }}</span>
                </a>
                <div class="flex shrink-0 items-center gap-2">
                    <div class="relative">
                        <button type="button" id="backend-menu-btn" class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-white/90 hover:bg-white/10 hover:text-white" aria-expanded="false" aria-haspopup="true">Menu <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                        <div id="backend-menu-dropdown" class="nav-dropdown-panel absolute right-0 top-full z-10 mt-1 hidden min-w-[200px] max-w-[calc(100vw-2rem)] rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5">
                            <a href="{{ route($dashboardRouteName) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="dashboard" class="h-4 w-4 shrink-0 text-slate-400" />Dashboard</a>
                            @if(auth()->user()->hasTenantPermission('view appointments'))
                            <a href="{{ route('backend.appointments.index') }}" class="flex items-center justify-between gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><span class="inline-flex items-center gap-2"><x-tenant-nav-icon name="appointments" class="h-4 w-4 shrink-0 text-slate-400" />Appointments</span> @if(($backendPendingAppointmentsCount ?? 0) > 0)<span class="rounded-full bg-emerald-500 px-1.5 py-0.5 text-xs font-semibold text-white">{{ $backendPendingAppointmentsCount }}</span>@endif</a>
                            @endif
                            @if(auth()->user()->hasTenantBarangayAdministrationAccess())
                            <a href="{{ route('backend.services.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="services" class="h-4 w-4 shrink-0 text-slate-400" />Services</a>
                            @endif
                            @if(auth()->user()->hasTenantPermission('view reports'))
                            <a href="{{ route('backend.reports.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="reports" class="h-4 w-4 shrink-0 text-slate-400" />Reports</a>
                            @endif
                            @if(!$isResidentPortal)
                            <a href="{{ route('backend.users.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="users" class="h-4 w-4 shrink-0 text-slate-400" />Users</a>
                            @endif
                            <a href="{{ route($supportRouteName) }}" class="flex items-center justify-between gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><span class="inline-flex min-w-0 items-center gap-2"><x-tenant-nav-icon name="support" class="h-4 w-4 shrink-0 text-slate-400" />Support &amp; Updates</span> @if(($supportUpdatesNotificationCount ?? 0) > 0)<span class="rounded-full bg-emerald-500 px-1.5 py-0.5 text-xs font-semibold text-white">{{ $supportUpdatesNotificationCount }}</span>@endif</a>
                            @if(auth()->user()->hasTenantBarangayAdministrationAccess())
                            <a href="{{ route('backend.pending-approvals.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="approvals" class="h-4 w-4 shrink-0 text-slate-400" />Approvals</a>
                            <a href="{{ route('backend.announcements.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="announcements" class="h-4 w-4 shrink-0 text-slate-400" />Announcements</a>
                            <a href="{{ route('backend.events.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="events" class="h-4 w-4 shrink-0 text-slate-400" />Events</a>
                            <a href="{{ route('backend.rbac.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="rbac" class="h-4 w-4 shrink-0 text-slate-400" />Roles</a>
                            <a href="{{ route('backend.audit-log.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="audit-log" class="h-4 w-4 shrink-0 text-slate-400" />Audit log</a>
                            @if($hasFeatureWebCustomization ?? false)
                            <a href="{{ route('backend.customize-web.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-tenant-nav-icon name="customize" class="h-4 w-4 shrink-0 text-slate-400" />Customize</a>
                            @endif
                            @endif
                            @planFeature('inventory')
                            @if(auth()->user()->hasTenantPermission('manage inventory'))
                            <a href="{{ route('backend.inventory.index') }}" class="flex items-center justify-between gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><span class="inline-flex items-center gap-2"><x-tenant-nav-icon name="inventory" class="h-4 w-4 shrink-0 text-slate-400" />Inventory</span> @if(($backendMedicineAcquisitionNotifyCount ?? 0) > 0)<span class="rounded-full bg-emerald-500 px-1.5 py-0.5 text-xs font-semibold text-white">{{ $backendMedicineAcquisitionNotifyCount }}</span>@endif</a>
                            @endif
                            @if(auth()->user()->hasTenantPermission('manage medicine'))
                            <a href="{{ route('backend.medicines.index') }}" class="flex items-center justify-between gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"><span class="inline-flex items-center gap-2"><x-tenant-nav-icon name="medicine" class="h-4 w-4 shrink-0 text-slate-400" />Medicine</span> @if(($backendMedicineAcquisitionNotifyCount ?? 0) > 0)<span class="rounded-full bg-emerald-500 px-1.5 py-0.5 text-xs font-semibold text-white">{{ $backendMedicineAcquisitionNotifyCount }}</span>@endif</a>
                            @endif
                            @endplanFeature
                        </div>
                    </div>
                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-white hover:bg-white/10">
                            <span class="max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="absolute right-0 top-full z-10 mt-1 w-48 rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="session_portal" value="{{ $sessionPortalKey ?? 'public' }}">
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Logout</button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </nav>
    @else
    <!-- Navbar -->
    <nav class="tenant-brand-nav sticky top-0 z-50 border-b border-white/20 bg-teal-600 shadow-sm" id="backend-nav" data-brand-color="{{ e($brandColor) }}">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 min-w-0 items-center justify-between gap-2">
                {{-- Brand --}}
                <div class="flex min-w-0 shrink-0 items-center gap-2">
                    <button type="button" id="mobile-menu-btn" class="rounded-lg p-1.5 text-white/90 hover:bg-white/10 lg:hidden" aria-label="Menu">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route($dashboardRouteName) }}" class="flex min-w-0 items-center gap-2 font-semibold text-white">
                        @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="{{ $brandLogoClass ?? 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25' }}">@endif
                        <span class="truncate text-sm">{{ $brandName }}</span>
                    </a>
                </div>

                {{-- Primary nav links (desktop) --}}
                @php
                    $navDefault = 'whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-medium text-white/80 transition hover:bg-white/15 hover:text-white';
                    $navActive  = 'whitespace-nowrap rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-teal-700 shadow-sm';
                    $moreIsActive = request()->routeIs('backend.rbac.*') || request()->routeIs('backend.audit-log.*') || request()->routeIs('backend.customize-web.*') || request()->routeIs('backend.inventory.*') || request()->routeIs('backend.medicines.*');
                @endphp
                <div id="nav-links" class="hidden items-center gap-0.5 lg:flex">
                    <a href="{{ route($dashboardRouteName) }}" class="{{ $dashboardIsActive ? $navActive : $navDefault }} inline-flex items-center gap-1"><x-tenant-nav-icon name="dashboard" class="h-3.5 w-3.5 shrink-0 opacity-90" />Dashboard</a>
                    @if(auth()->user()->hasTenantPermission('view appointments'))
                    <a href="{{ route('backend.appointments.index') }}" class="relative inline-flex items-center gap-1 {{ request()->routeIs('backend.appointments.*') ? $navActive : $navDefault }} {{ ($backendPendingAppointmentsCount ?? 0) > 0 ? 'ring-1 ring-emerald-400' : '' }}">
                        <x-tenant-nav-icon name="appointments" class="h-3.5 w-3.5 shrink-0 opacity-90" />Appointments
                        @if(($backendPendingAppointmentsCount ?? 0) > 0)<span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-emerald-500 px-0.5 text-[10px] font-bold text-white">{{ $backendPendingAppointmentsCount }}</span>@endif
                    </a>
                    @endif
                    @if(auth()->user()->hasTenantBarangayAdministrationAccess())
                    <a href="{{ route('backend.services.index') }}" class="{{ request()->routeIs('backend.services.*') ? $navActive : $navDefault }} inline-flex items-center gap-1"><x-tenant-nav-icon name="services" class="h-3.5 w-3.5 shrink-0 opacity-90" />Services</a>
                    @endif
                    @if(auth()->user()->hasTenantPermission('view reports'))
                    <a href="{{ route('backend.reports.index') }}" class="{{ request()->routeIs('backend.reports.*') ? $navActive : $navDefault }} inline-flex items-center gap-1"><x-tenant-nav-icon name="reports" class="h-3.5 w-3.5 shrink-0 opacity-90" />Reports</a>
                    @endif
                    @if(!$isResidentPortal)
                    <a href="{{ route('backend.users.index') }}" class="{{ request()->routeIs('backend.users.*') ? $navActive : $navDefault }} inline-flex items-center gap-1"><x-tenant-nav-icon name="users" class="h-3.5 w-3.5 shrink-0 opacity-90" />Users</a>
                    @endif
                    <a href="{{ route($supportRouteName) }}" class="relative inline-flex items-center gap-1 {{ request()->routeIs('backend.support.*') || request()->routeIs('resident.support.*') ? $navActive : $navDefault }} {{ ($supportUpdatesNotificationCount ?? 0) > 0 ? 'ring-1 ring-emerald-400' : '' }}"><x-tenant-nav-icon name="support" class="h-3.5 w-3.5 shrink-0 opacity-90" />Support @if(($supportUpdatesNotificationCount ?? 0) > 0)<span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-emerald-500 px-0.5 text-[10px] font-bold text-white">{{ $supportUpdatesNotificationCount }}</span>@endif</a>
                    @if(auth()->user()->hasTenantBarangayAdministrationAccess())
                    <a href="{{ route('backend.pending-approvals.index') }}" class="relative inline-flex items-center gap-1 {{ request()->routeIs('backend.pending-approvals.*') ? $navActive : $navDefault }}">
                        <x-tenant-nav-icon name="approvals" class="h-3.5 w-3.5 shrink-0 opacity-90" />Approvals
                        @if(($backendPendingCount ?? 0) > 0)<span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-teal-900">{{ $backendPendingCount }}</span>@endif
                    </a>
                    <a href="{{ route('backend.announcements.index') }}" class="{{ request()->routeIs('backend.announcements.*') ? $navActive : $navDefault }} inline-flex items-center gap-1"><x-tenant-nav-icon name="announcements" class="h-3.5 w-3.5 shrink-0 opacity-90" />Announcements</a>
                    <a href="{{ route('backend.events.index') }}" class="{{ request()->routeIs('backend.events.*') ? $navActive : $navDefault }} inline-flex items-center gap-1"><x-tenant-nav-icon name="events" class="h-3.5 w-3.5 shrink-0 opacity-90" />Events</a>
                    {{-- More dropdown for secondary links --}}
                    <div class="relative" id="more-menu-wrapper">
                        <button type="button" id="more-menu-btn" class="inline-flex items-center gap-1 {{ $moreIsActive ? $navActive : $navDefault }}">
                            <x-tenant-nav-icon name="more" class="h-3.5 w-3.5 shrink-0 opacity-90" />More
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="more-menu-dropdown" class="absolute right-0 top-full z-20 mt-1.5 hidden min-w-[180px] rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5">
                            <a href="{{ route('backend.rbac.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm {{ request()->routeIs('backend.rbac.*') ? 'bg-teal-50 font-medium text-teal-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                <svg class="h-4 w-4 {{ request()->routeIs('backend.rbac.*') ? 'text-teal-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Roles &amp; Permissions
                            </a>
                            <a href="{{ route('backend.audit-log.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm {{ request()->routeIs('backend.audit-log.*') ? 'bg-teal-50 font-medium text-teal-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                <svg class="h-4 w-4 {{ request()->routeIs('backend.audit-log.*') ? 'text-teal-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Audit log
                            </a>
                            @if($hasFeatureWebCustomization ?? false)
                            <a href="{{ route('backend.customize-web.edit') }}" class="flex items-center gap-2 px-3 py-2 text-sm {{ request()->routeIs('backend.customize-web.*') ? 'bg-teal-50 font-medium text-teal-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                <svg class="h-4 w-4 {{ request()->routeIs('backend.customize-web.*') ? 'text-teal-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                Customize
                            </a>
                            @endif
                            @planFeature('inventory')
                            @if(auth()->user()->hasTenantPermission('manage inventory'))
                            <a href="{{ route('backend.inventory.index') }}" class="flex items-center justify-between gap-2 px-3 py-2 text-sm {{ request()->routeIs('backend.inventory.*') ? 'bg-teal-50 font-medium text-teal-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 {{ request()->routeIs('backend.inventory.*') ? 'text-teal-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                Inventory
                                </span>
                                @if(($backendMedicineAcquisitionNotifyCount ?? 0) > 0)<span class="rounded-full bg-emerald-500 px-1.5 py-0.5 text-xs font-semibold text-white">{{ $backendMedicineAcquisitionNotifyCount }}</span>@endif
                            </a>
                            @endif
                            @if(auth()->user()->hasTenantPermission('manage medicine'))
                            <a href="{{ route('backend.medicines.index') }}" class="flex items-center justify-between gap-2 px-3 py-2 text-sm {{ request()->routeIs('backend.medicines.*') ? 'bg-teal-50 font-medium text-teal-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 {{ request()->routeIs('backend.medicines.*') ? 'text-teal-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5V19a2 2 0 01-2 2H6.5a2 2 0 01-2-2v-8.5M12 3v4m0 0l2-2m-2 2L10 7M4.5 10.5h15"/></svg>
                                Medicine
                                </span>
                                @if(($backendMedicineAcquisitionNotifyCount ?? 0) > 0)<span class="rounded-full bg-emerald-500 px-1.5 py-0.5 text-xs font-semibold text-white">{{ $backendMedicineAcquisitionNotifyCount }}</span>@endif
                            </a>
                            @endif
                            @endplanFeature
                        </div>
                    </div>
                    @endif
                </div>

                {{-- User menu --}}
                <div class="relative flex shrink-0 items-center">
                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-white hover:bg-white/10">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/20 text-xs font-semibold text-white">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            <span class="hidden max-w-[100px] truncate sm:inline">{{ auth()->user()->name }}</span>
                            <svg class="h-3 w-3 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="absolute right-0 top-full z-10 mt-1.5 w-52 rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5">
                            <div class="border-b border-slate-100 px-3 py-2">
                                <p class="truncate text-sm font-medium text-slate-800">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $tenant?->name ?? 'Super Admin' }}</p>
                            </div>
                            <a href="{{ route($profileRouteName) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Profile
                            </a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="session_portal" value="{{ $sessionPortalKey ?? 'public' }}">
                                <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>

            {{-- Mobile menu --}}
            @php
                $mobDefault = 'rounded-lg px-3 py-2 text-sm text-white/80 hover:bg-white/10';
                $mobActive  = 'rounded-lg bg-white/25 px-3 py-2 text-sm font-semibold text-white border-l-4 border-white';
            @endphp
            <div id="mobile-menu" class="hidden border-t border-white/20 pb-3 pt-2 lg:hidden">
                <div class="flex flex-col gap-0.5">
                    <a href="{{ route($dashboardRouteName) }}" class="{{ $dashboardIsActive ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="dashboard" class="h-4 w-4 shrink-0 opacity-90" />Dashboard</a>
                    @if(auth()->user()->hasTenantPermission('view appointments'))
                    <a href="{{ route('backend.appointments.index') }}" class="{{ request()->routeIs('backend.appointments.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="appointments" class="h-4 w-4 shrink-0 opacity-90" />Appointments @if(($backendPendingAppointmentsCount ?? 0) > 0)<span class="ml-1 rounded-full bg-emerald-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $backendPendingAppointmentsCount }}</span>@endif</a>
                    @endif
                    @if(auth()->user()->hasTenantBarangayAdministrationAccess())
                    <a href="{{ route('backend.services.index') }}" class="{{ request()->routeIs('backend.services.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="services" class="h-4 w-4 shrink-0 opacity-90" />Services</a>
                    @endif
                    @if(auth()->user()->hasTenantPermission('view reports'))
                    <a href="{{ route('backend.reports.index') }}" class="{{ request()->routeIs('backend.reports.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="reports" class="h-4 w-4 shrink-0 opacity-90" />Reports</a>
                    @endif
                    @if(!$isResidentPortal)
                    <a href="{{ route('backend.users.index') }}" class="{{ request()->routeIs('backend.users.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="users" class="h-4 w-4 shrink-0 opacity-90" />Users</a>
                    @endif
                    <a href="{{ route($supportRouteName) }}" class="{{ request()->routeIs('backend.support.*') || request()->routeIs('resident.support.*') ? $mobActive : $mobDefault }} {{ ($supportUpdatesNotificationCount ?? 0) > 0 ? 'ring-1 ring-emerald-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center gap-2"><x-tenant-nav-icon name="support" class="h-4 w-4 shrink-0 opacity-90" />Support &amp; Updates @if(($supportUpdatesNotificationCount ?? 0) > 0)<span class="ml-1 rounded-full bg-emerald-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $supportUpdatesNotificationCount }}</span>@endif</a>
                    @if(auth()->user()->hasTenantBarangayAdministrationAccess())
                    <a href="{{ route('backend.pending-approvals.index') }}" class="{{ request()->routeIs('backend.pending-approvals.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="approvals" class="h-4 w-4 shrink-0 opacity-90" />Approvals @if(($backendPendingCount ?? 0) > 0)<span class="ml-1 rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $backendPendingCount }}</span>@endif</a>
                    <a href="{{ route('backend.announcements.index') }}" class="{{ request()->routeIs('backend.announcements.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="announcements" class="h-4 w-4 shrink-0 opacity-90" />Announcements</a>
                    <a href="{{ route('backend.events.index') }}" class="{{ request()->routeIs('backend.events.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="events" class="h-4 w-4 shrink-0 opacity-90" />Events</a>
                    <a href="{{ route('backend.rbac.index') }}" class="{{ request()->routeIs('backend.rbac.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="rbac" class="h-4 w-4 shrink-0 opacity-90" />Roles &amp; Permissions</a>
                    <a href="{{ route('backend.audit-log.index') }}" class="{{ request()->routeIs('backend.audit-log.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="audit-log" class="h-4 w-4 shrink-0 opacity-90" />Audit log</a>
                    @if($hasFeatureWebCustomization ?? false)
                    <a href="{{ route('backend.customize-web.edit') }}" class="{{ request()->routeIs('backend.customize-web.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="customize" class="h-4 w-4 shrink-0 opacity-90" />Customize</a>
                    @endif
                    @endif
                    @planFeature('inventory')
                    @if(auth()->user()->hasTenantPermission('manage inventory'))
                    <a href="{{ route('backend.inventory.index') }}" class="{{ request()->routeIs('backend.inventory.*') ? $mobActive : $mobDefault }} {{ ($backendMedicineAcquisitionNotifyCount ?? 0) > 0 ? 'ring-1 ring-emerald-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center gap-2"><x-tenant-nav-icon name="inventory" class="h-4 w-4 shrink-0 opacity-90" />Inventory @if(($backendMedicineAcquisitionNotifyCount ?? 0) > 0)<span class="ml-1 rounded-full bg-emerald-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $backendMedicineAcquisitionNotifyCount }}</span>@endif</a>
                    @endif
                    @if(auth()->user()->hasTenantPermission('manage medicine'))
                    <a href="{{ route('backend.medicines.index') }}" class="{{ request()->routeIs('backend.medicines.*') ? $mobActive : $mobDefault }} {{ ($backendMedicineAcquisitionNotifyCount ?? 0) > 0 ? 'ring-1 ring-emerald-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center gap-2"><x-tenant-nav-icon name="medicine" class="h-4 w-4 shrink-0 opacity-90" />Medicine @if(($backendMedicineAcquisitionNotifyCount ?? 0) > 0)<span class="ml-1 rounded-full bg-emerald-400 px-1.5 py-0.5 text-xs font-semibold text-teal-900">{{ $backendMedicineAcquisitionNotifyCount }}</span>@endif</a>
                    @endif
                    @endplanFeature
                    <div class="my-1 border-t border-white/20"></div>
                    <a href="{{ route($profileRouteName) }}" class="{{ request()->routeIs('backend.profile.*') || request()->routeIs('resident.profile.*') ? $mobActive : $mobDefault }} inline-flex items-center gap-2"><x-tenant-nav-icon name="profile" class="h-4 w-4 shrink-0 opacity-90" />Profile</a>
                </div>
            </div>
        </div>
    </nav>
    @endif

    @php($appVersion = trim((string) ($appVersion ?? config('app.version', ''))))
    <main class="px-4 py-6 sm:px-6 lg:px-8 @if($navLayout === 'sidebar') main-with-sidebar pt-20 md:pt-6 @else mx-auto {{ $tenantMainMaxWidthClass ?? 'max-w-7xl' }} @endif">
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
        @if($errors->any())
            <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-rose-800 ring-1 ring-rose-200" role="alert">
                <p class="font-medium">{{ __('Please correct the following:') }}</p>
                <ul class="mt-2 list-inside list-disc text-sm">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    @if(($tenant && $tenant->footer_text) || $appVersion !== '')
    <footer class="mt-auto border-t border-slate-200 bg-white py-4">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 text-center text-sm text-slate-500 sm:px-6 lg:px-8">
            @if($tenant && $tenant->footer_text)
                <span>{{ $tenant->footer_text }}</span>
            @endif
            @if($appVersion !== '')
                <span class="font-medium">Version {{ $appVersion }}</span>
            @endif
        </div>
    </footer>
    @endif

    <script>
    (function() {
        var brandColor = function(el) {
            if (el) {
                var c = el.getAttribute('data-brand-color') || '#0d9488';
                el.style.background = 'linear-gradient(135deg, ' + c + ' 0%, ' + c + 'dd 100%)';
            }
        };
        [document.getElementById('backend-nav'), document.getElementById('backend-sidebar-header')].forEach(brandColor);

        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
            document.getElementById('nav-links').classList.add('hidden');
        });
        var menuBtn = document.getElementById('backend-menu-btn');
        var menuDrop = document.getElementById('backend-menu-dropdown');
        if (menuBtn && menuDrop) {
            menuBtn.addEventListener('click', function() {
                var open = menuDrop.classList.toggle('hidden');
                menuBtn.setAttribute('aria-expanded', open ? 'false' : 'true');
            });
            document.addEventListener('click', function(e) {
                if (!menuBtn.contains(e.target) && !menuDrop.contains(e.target)) {
                    menuDrop.classList.add('hidden');
                    menuBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // More dropdown menu
        var moreBtn = document.getElementById('more-menu-btn');
        var moreDrop = document.getElementById('more-menu-dropdown');
        if (moreBtn && moreDrop) {
            moreBtn.addEventListener('click', function() {
                moreDrop.classList.toggle('hidden');
            });
            document.addEventListener('click', function(e) {
                if (!moreBtn.contains(e.target) && !moreDrop.contains(e.target)) {
                    moreDrop.classList.add('hidden');
                }
            });
        }

        var sidebarOpen = document.getElementById('backend-sidebar-open');
        var sidebarClose = document.getElementById('backend-sidebar-close');
        var sidebarOverlay = document.getElementById('backend-sidebar-overlay');
        function closeBackendSidebar() {
            document.body.classList.remove('sidebar-open');
        }
        if (sidebarOpen) sidebarOpen.addEventListener('click', function() { document.body.classList.add('sidebar-open'); });
        if (sidebarClose) sidebarClose.addEventListener('click', closeBackendSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeBackendSidebar);

        // Real-time updates (Reverb): debounced reload for tenant-wide changes; profile only for the signed-in user.
        var tenantId = document.body.getAttribute('data-tenant-id');
        var currentUserId = document.body.getAttribute('data-current-user-id');
        function subscribeRealtime() {
            if (!tenantId || typeof window.Echo === 'undefined') return;
            var reloadTimer = null;
            function debouncedReload() {
                if (reloadTimer) clearTimeout(reloadTimer);
                reloadTimer = setTimeout(function() { location.reload(); }, 450);
            }
            var ch = window.Echo.channel('tenant.' + tenantId);
            ch.listen('.customization.updated', debouncedReload);
            ch.listen('.rbac.updated', debouncedReload);
            ch.listen('.appointment.updated', debouncedReload);
            ch.listen('.profile.updated', function(e) {
                if (!currentUserId || !e || e.user_id === undefined || e.user_id === null) return;
                if (String(e.user_id) !== String(currentUserId)) return;
                debouncedReload();
            });
        }
        if (typeof window.Echo !== 'undefined') subscribeRealtime();
        else window.addEventListener('echo-ready', subscribeRealtime);
    })();
    </script>
    @include('components.professional-alerts')
    @include('components.session-tab-sync')
    @stack('scripts')
</body>
</html>
