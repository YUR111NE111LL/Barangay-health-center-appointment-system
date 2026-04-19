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
    <title>@yield('title', 'Tenant User') – {{ $brandName ?? config('bhcas.name') }}</title>
    <script>
        window.reverbConfig = (function() {
            var raw = document.documentElement.getAttribute('data-reverb-config');
            return raw ? JSON.parse(raw) : {};
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(!empty($fontUrl))
    <link rel="stylesheet" href="{{ $fontUrl }}">
    @endif
    @if($tenant)
    <link rel="stylesheet" href="{{ route('tenant.custom-css') }}">
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased {{ $themeClass }} @if($navLayout === 'sidebar')layout-sidebar @endif" data-theme="{{ e($tenant?->theme ?? 'default') }}" data-nav-layout="{{ e($navLayout) }}" data-session-portal="{{ $sessionPortalKey ?? 'public' }}" @if($tenant) data-tenant-id="{{ $tenant->id }}" @endif @if(auth()->check()) data-current-user-id="{{ auth()->id() }}" @endif>
    @php
        $portalLabel = auth()->user()?->role ?: 'Tenant User';
    @endphp
    @if($navLayout === 'sidebar')
    <div class="sidebar-overlay" id="resident-sidebar-overlay" aria-hidden="true"></div>
    <aside class="tenant-brand-nav sidebar-drawer fixed left-0 top-0 z-40 flex h-full w-56 flex-col border-r border-white/10 bg-teal-600 shadow-lg md:translate-x-0" id="resident-nav" data-brand-color="{{ e($brandColor) }}">
        <div class="flex h-14 items-center justify-between gap-2 border-b border-white/20 px-4">
            <div class="flex min-w-0 items-center gap-2">
                @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="{{ $brandLogoClass ?? 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25' }}">@endif
                <span class="truncate font-semibold text-white">{{ $brandName }} – {{ $portalLabel }}</span>
            </div>
            <button type="button" id="resident-sidebar-close" class="rounded-lg p-2 text-white/90 hover:bg-white/10 md:hidden" aria-label="Close menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @php
            $rsbDefault = 'rounded-lg px-3 py-2.5 text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white transition';
            $rsbActive  = 'rounded-lg bg-white/25 px-3 py-2.5 text-sm font-semibold text-white border-l-4 border-white';
        @endphp
        <nav class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto p-3">
            @foreach($residentNavItems as $item)
            <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route'] . '*') || request()->routeIs($item['route']) ? $rsbActive : $rsbDefault }} {{ !empty($item['badge']) ? 'ring-1 ring-emerald-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center justify-between gap-2">
                <span class="inline-flex min-w-0 items-center gap-2">
                    <x-tenant-nav-icon :name="$item['icon'] ?? 'default'" class="h-4 w-4 opacity-90" />
                    <span class="truncate">{{ $item['label'] }}</span>
                </span>
                @if(!empty($item['badge']))
                    <span class="inline-flex min-w-5 shrink-0 items-center justify-center rounded-full bg-emerald-400 px-1.5 py-0.5 text-[10px] font-semibold text-teal-900">{{ $item['badge'] }}</span>
                @endif
            </a>
            @endforeach
            <form action="{{ route('logout') }}" method="POST" class="mt-2">
                @csrf
                <input type="hidden" name="session_portal" value="{{ $sessionPortalKey ?? 'public' }}">
                <button type="submit" class="inline-flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-white/90 hover:bg-white/10 hover:text-white">
                    <x-tenant-nav-icon name="logout" class="h-4 w-4 opacity-90" />
                    Logout
                </button>
            </form>
        </nav>
    </aside>
    <header class="fixed left-0 right-0 top-0 z-30 flex h-14 items-center justify-between border-b border-white/20 bg-teal-600 px-4 md:hidden" id="resident-sidebar-header" data-brand-color="{{ e($brandColor) }}">
        <button type="button" id="resident-sidebar-open" class="rounded-lg p-2 text-white/90 hover:bg-white/10" aria-label="Open menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="flex min-w-0 items-center justify-center gap-2 font-semibold text-white">
            @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="{{ $brandLogoClass ?? 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25' }}">@endif
            <span class="truncate">{{ $brandName }}</span>
        </span>
        <div class="w-10"></div>
    </header>
    @elseif($navLayout === 'dropdown')
    <nav class="tenant-brand-nav sticky top-0 z-50 border-b border-white/20 bg-teal-600 shadow-sm" id="resident-nav" data-brand-color="{{ e($brandColor) }}">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 min-w-0 items-center justify-between gap-2">
                <a href="{{ route('resident.dashboard') }}" class="flex min-w-0 shrink-0 items-center gap-2 font-semibold text-white">
                    @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="{{ $brandLogoClass ?? 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25' }}">@endif
                    <span class="truncate">{{ $brandName }} – {{ $portalLabel }}</span>
                </a>
                <div class="relative shrink-0">
                    <button type="button" id="resident-menu-btn" class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-white/90 hover:bg-white/10 hover:text-white" aria-expanded="false" aria-haspopup="true">
                        Menu <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="resident-menu-dropdown" class="nav-dropdown-panel absolute right-0 top-full z-10 mt-1 hidden min-w-[180px] rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5">
                        @foreach($residentNavItems as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center justify-between gap-2 px-4 py-2 text-sm {{ request()->routeIs($item['route'] . '*') || request()->routeIs($item['route']) ? 'bg-teal-50 font-medium text-teal-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <span class="inline-flex min-w-0 items-center gap-2">
                                <x-tenant-nav-icon :name="$item['icon'] ?? 'default'" class="h-4 w-4 shrink-0 {{ request()->routeIs($item['route'] . '*') || request()->routeIs($item['route']) ? 'text-teal-600' : 'text-slate-400' }}" />
                                <span>{{ $item['label'] }}</span>
                            </span>
                            @if(!empty($item['badge']))
                                <span class="inline-flex min-w-5 shrink-0 items-center justify-center rounded-full bg-emerald-400 px-1.5 py-0.5 text-[10px] font-semibold text-teal-900">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                        @endforeach
                        <form action="{{ route('logout') }}" method="POST" class="border-t border-slate-100">
                            @csrf
                            <input type="hidden" name="session_portal" value="{{ $sessionPortalKey ?? 'public' }}">
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                <x-tenant-nav-icon name="logout" class="h-4 w-4 text-slate-400" />
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    @else
    <nav class="tenant-brand-nav sticky top-0 z-50 border-b border-white/20 bg-teal-600 shadow-sm" id="resident-nav" data-brand-color="{{ e($brandColor) }}">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 min-w-0 items-center justify-between gap-2">
                <a href="{{ route('resident.dashboard') }}" class="flex min-w-0 shrink-0 items-center gap-2 font-semibold text-white">
                    @if($brandLogo)<img src="{{ $brandLogo }}" alt="" class="{{ $brandLogoClass ?? 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25' }}">@endif
                    <span class="truncate">{{ $brandName }} – {{ $portalLabel }}</span>
                </a>
                <button type="button" id="resident-nav-mobile-btn" class="rounded-lg p-2 text-white/90 hover:bg-white/10 md:hidden" aria-label="Menu" aria-expanded="false">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                @php
                    $rNavDefault = 'whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium text-white/80 transition hover:bg-white/15 hover:text-white';
                    $rNavActive  = 'whitespace-nowrap rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-teal-700 shadow-sm';
                @endphp
                <div id="resident-nav-links" class="hidden flex-wrap items-center gap-1 md:flex">
                    @foreach($residentNavItems as $item)
                    <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route'] . '*') || request()->routeIs($item['route']) ? $rNavActive : $rNavDefault }} {{ !empty($item['badge']) ? 'ring-1 ring-emerald-400' : '' }} inline-flex items-center gap-1.5">
                        <x-tenant-nav-icon :name="$item['icon'] ?? 'default'" class="h-3.5 w-3.5 opacity-90" />
                        <span>{{ $item['label'] }}</span>
                        @if(!empty($item['badge']))
                            <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-emerald-400 px-1.5 py-0.5 text-[10px] font-semibold text-teal-900">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                    @endforeach
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="session_portal" value="{{ $sessionPortalKey ?? 'public' }}">
                        <button type="submit" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium text-white/80 transition hover:bg-white/15 hover:text-white">
                            <x-tenant-nav-icon name="logout" class="h-3.5 w-3.5 opacity-90" />
                            Logout
                        </button>
                    </form>
                </div>
            </div>
            @php
                $rMobDefault = 'rounded-lg px-3 py-2 text-sm text-white/80 hover:bg-white/10';
                $rMobActive  = 'rounded-lg bg-white/25 px-3 py-2 text-sm font-semibold text-white border-l-4 border-white';
            @endphp
            <div id="resident-nav-mobile" class="hidden border-t border-white/20 px-4 py-3 md:hidden">
                <div class="flex flex-col gap-1">
                    @foreach($residentNavItems as $item)
                    <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route'] . '*') || request()->routeIs($item['route']) ? $rMobActive : $rMobDefault }} {{ !empty($item['badge']) ? 'ring-1 ring-emerald-400 ring-offset-2 ring-offset-teal-600' : '' }} inline-flex items-center justify-between gap-2">
                        <span class="inline-flex min-w-0 items-center gap-2">
                            <x-tenant-nav-icon :name="$item['icon'] ?? 'default'" class="h-4 w-4 opacity-90" />
                            <span>{{ $item['label'] }}</span>
                        </span>
                        @if(!empty($item['badge']))
                            <span class="inline-flex min-w-5 shrink-0 items-center justify-center rounded-full bg-emerald-400 px-1.5 py-0.5 text-[10px] font-semibold text-teal-900">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                    @endforeach
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="session_portal" value="{{ $sessionPortalKey ?? 'public' }}">
                        <button type="submit" class="inline-flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-white/90 hover:bg-white/10">
                            <x-tenant-nav-icon name="logout" class="h-4 w-4 opacity-90" />
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endif

    <main class="px-4 py-8 sm:px-6 lg:px-8 @if($navLayout === 'sidebar') main-with-sidebar pt-14 md:pt-8 @else mx-auto {{ $tenantMainMaxWidthClass ?? 'max-w-4xl' }} @endif">
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
        @yield('content')
    </main>
    <script>
    (function() {
        var brandColor = function(el) {
            if (el) {
                var c = el.getAttribute('data-brand-color') || '#0d9488';
                el.style.background = 'linear-gradient(135deg, ' + c + ' 0%, ' + c + 'dd 100%)';
            }
        };
        [document.getElementById('resident-nav'), document.getElementById('resident-sidebar-header')].forEach(brandColor);

        var menuBtn = document.getElementById('resident-menu-btn');
        var menuDrop = document.getElementById('resident-menu-dropdown');
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

        var sidebarOpen = document.getElementById('resident-sidebar-open');
        var sidebarClose = document.getElementById('resident-sidebar-close');
        var sidebarOverlay = document.getElementById('resident-sidebar-overlay');
        function closeSidebar() {
            document.body.classList.remove('sidebar-open');
        }
        function openSidebar() {
            document.body.classList.add('sidebar-open');
        }
        if (sidebarOpen) sidebarOpen.addEventListener('click', openSidebar);
        if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        var mobileBtn = document.getElementById('resident-nav-mobile-btn');
        var mobileMenu = document.getElementById('resident-nav-mobile');
        if (mobileBtn && mobileMenu) {
            mobileBtn.addEventListener('click', function() {
                var open = mobileMenu.classList.toggle('hidden');
                mobileBtn.setAttribute('aria-expanded', open ? 'false' : 'true');
            });
        }

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
    @if($tenant && $tenant->footer_text)
    <footer class="mt-auto border-t border-slate-200 bg-white py-4">
        <div class="mx-auto max-w-7xl px-4 text-center text-sm text-slate-500 sm:px-6 lg:px-8">{{ $tenant->footer_text }}</div>
    </footer>
    @endif
</body>
</html>
