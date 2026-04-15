@php
    $logoPath = config('bhcas.logo_path');
    $logoUrl = $logoPath ? asset($logoPath) : null;
    $loginBg = config('bhcas.login_background', 'teal');
    $loginBgColor = config('bhcas.login_background_color');
    if ($loginBg === 'custom' && $loginBgColor) {
        $loginBgClass = '';
        $loginBgStyle = 'background: ' . e($loginBgColor) . ';';
    } elseif ($loginBg === 'slate') {
        $loginBgClass = 'bg-slate-200';
        $loginBgStyle = '';
    } else {
        $loginBgClass = 'bg-gradient-to-br from-teal-500 via-teal-600 to-cyan-700';
        $loginBgStyle = '';
    }

    $loginOuterExtraClass = ($loginBg !== 'custom' && $loginBg !== 'slate') ? 'login-central-outer' : '';
    $loginBgOuterClass = $loginBg === 'custom' ? '' : $loginBgClass;
    $loginBgStyleAttr = $loginBgStyle !== '' ? 'style="' . e($loginBgStyle) . '"' : '';

    // Tenant list for "Sign in as Resident/Staff" quick links (central domain).
    $tenants = \App\Models\Tenant::with('domains')
        ->where('is_active', true)
        ->whereHas('domains', static fn ($q) => $q->whereNotNull('domain')->where('domain', '!=', ''))
        ->orderBy('name')
        ->get();
    $scheme = request()->getScheme();
    $port = request()->getPort();
    $portSuffix = ($port && ! in_array((int) $port, [80, 443], true)) ? ':' . $port : '';
    $authScopeAlertMessage = session('auth_scope_alert') ?: request()->query('auth_error');
    $displayAuthErrorMessage = $errors->first() ?: $authScopeAlertMessage;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Central Login – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(\App\Support\Recaptcha::shouldLoadClient())
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.v3.site_key') }}" async defer></script>
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    <style>
        .login-panel-left { background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%); }
        .login-central-outer { background: linear-gradient(135deg, #0d9488 0%, #0f766e 40%, #0891b2 100%); }
        @media (max-width: 767px) { .login-panel-left { min-height: 12rem; } }
    </style>
</head>
<body
    class="min-h-screen overflow-x-hidden antialiased"
    style="font-family: 'DM Sans', ui-sans-serif, sans-serif;"
    data-auth-scope-alert="{{ (string) ($authScopeAlertMessage ?? '') }}"
    data-auth-first-error="{{ (string) ($displayAuthErrorMessage ?? '') }}"
>
    <div class="min-h-screen overflow-visible flex items-center justify-center p-4 {{ $loginBgOuterClass }} {{ $loginOuterExtraClass }}" {!! $loginBgStyleAttr !!}>
        <div class="w-full max-w-4xl rounded-2xl overflow-hidden shadow-2xl ring-1 ring-slate-300/50 flex flex-col md:flex-row bg-white">
            <div class="login-panel-left md:w-[44%] flex flex-col items-center justify-center p-8 md:p-12 text-white">
                <div class="w-full max-w-[280px] rounded-xl bg-white p-6 md:p-8 shadow-lg flex flex-col items-center justify-center text-center">
                    @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ config('bhcas.name') }}" class="max-w-full h-auto max-h-40 md:max-h-48 w-auto object-contain" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                    <div class="hidden">
                        <p class="text-slate-800 text-sm md:text-base font-semibold leading-tight">{{ config('bhcas.name') }}</p>
                        <div class="mt-3 flex justify-center gap-2 flex-wrap" aria-hidden="true">
                            <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                            <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></span>
                            <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                            <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
                            <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                        </div>
                    </div>
                    @else
                    <p class="text-slate-800 text-sm md:text-base font-semibold leading-tight">{{ config('bhcas.name') }}</p>
                    <div class="mt-3 flex justify-center gap-2 flex-wrap" aria-hidden="true">
                        <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600" title="Records"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                        <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600" title="Home"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></span>
                        <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600" title="Calendar"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                        <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600" title="Health"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
                        <span class="inline-flex rounded bg-slate-100 p-1.5 text-teal-600" title="Mobile"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                    </div>
                    @endif
                    @if($tenants->isNotEmpty())
                        <div class="mt-4 w-full max-h-[420px] overflow-y-auto pr-1">
                            <p class="text-xs font-semibold text-teal-700">Sign in at your barangay</p>
                            <p class="mt-1 text-[11px] text-slate-600">Choose a barangay, then pick Resident or Staff/Nurse.</p>

                            <div class="mt-3 flex flex-col gap-2">
                                @foreach($tenants as $t)
                                    @php $domain = $t->domains->first()?->domain; @endphp
                                    @if($domain)
                                        @php
                                            $hostOnly = explode(':', (string) $domain)[0];
                                            $firstLabel = explode('.', $hostOnly)[0] ?? '';
                                            $barangayDisplay = ucwords(str_replace('-', ' ', $firstLabel));
                                            $residentLoginUrl = $scheme . '://' . $domain . $portSuffix . '/login?for=resident';
                                            $staffLoginUrl = $scheme . '://' . $domain . $portSuffix . '/login?for=tenant';
                                        @endphp
                                        <div
                                            class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-100 hover:ring-teal-200 hover:shadow-sm cursor-pointer"
                                            role="link"
                                            tabindex="0"
                                            onclick="window.location.href='{{ $residentLoginUrl }}'"
                                            onkeydown="if(event.key==='Enter' || event.key===' '){ window.location.href='{{ $residentLoginUrl }}'; }"
                                        >
                                            <div class="truncate text-[12px] font-semibold text-slate-800">{{ $barangayDisplay }}</div>
                                            <div class="truncate text-[10px] text-slate-500">{{ $domain }}</div>

                                            <div class="mt-2 flex gap-2">
                                                <a
                                                    href="{{ $residentLoginUrl }}"
                                                    onclick="event.stopPropagation()"
                                                    class="flex-1 inline-flex items-center justify-center rounded-lg bg-teal-600 px-2 py-1 text-[10px] font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-teal-500"
                                                >
                                                    Resident
                                                </a>
                                                <a
                                                    href="{{ $staffLoginUrl }}"
                                                    onclick="event.stopPropagation()"
                                                    class="flex-1 inline-flex items-center justify-center rounded-lg border border-teal-600/30 bg-white px-2 py-1 text-[10px] font-semibold text-teal-800 shadow-sm transition hover:-translate-y-0.5 hover:bg-teal-50 hover:border-teal-600/50 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                                                >
                                                    Staff / Nurse
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                @if($tenants->isNotEmpty())
                    <div class="mt-4 hidden w-full max-w-[280px] rounded-xl bg-white/10 p-4 ring-1 ring-white/20">
                        <p class="text-sm font-semibold text-white">Sign in at your barangay</p>
                        <p class="mt-1 text-xs text-white/70">Choose barangay, then pick Resident or Staff/Nurse.</p>
                        <div class="mt-3 flex flex-col gap-3">
                            @foreach($tenants as $t)
                                @php $domain = $t->domains->first()?->domain; @endphp
                                @if($domain)
                                    <div class="rounded-lg bg-white/5 p-3 ring-1 ring-white/15">
                                        @php
                                            $hostOnly = explode(':', (string) $domain)[0];
                                            $firstLabel = explode('.', $hostOnly)[0] ?? '';
                                            $barangayDisplay = ucwords(str_replace('-', ' ', $firstLabel));
                                        @endphp
                                        <div class="truncate text-xs font-semibold text-white">{{ $barangayDisplay }}</div>
                                        <div class="truncate text-[11px] text-white/60">{{ $domain }}</div>
                                        <div class="mt-2 flex gap-2">
                                            <a
                                                href="{{ $scheme . '://' . $domain . $portSuffix . '/login?for=resident' }}"
                                                class="flex-1 inline-flex items-center justify-center rounded-lg bg-teal-600 px-2.5 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-teal-700"
                                            >
                                                Resident
                                            </a>
                                            <a
                                                href="{{ $scheme . '://' . $domain . $portSuffix . '/login?for=tenant' }}"
                                                class="flex-1 inline-flex items-center justify-center rounded-lg border border-teal-600/30 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-teal-800 shadow-sm transition hover:bg-teal-50 hover:border-teal-600/40"
                                            >
                                                Staff / Nurse
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
                {{-- Platform note removed by request --}}
            </div>
            <div class="flex-1 p-6 sm:p-8 md:p-10 flex flex-col justify-start">
                <div class="mb-6">
                    <p class="text-slate-500 text-sm">Central app</p>
                    <h1 class="text-2xl font-bold text-slate-800 mt-0.5">Super Admin Login</h1>
                    <p class="text-slate-500 text-xs mt-0.5">Platform administrator access. Staff or residents? Use your barangay’s own website to log in.</p>
                </div>
                @if($displayAuthErrorMessage)
                    <div data-auth-alert class="mb-4 rounded-xl border border-rose-300 bg-rose-100 px-4 py-3 text-sm font-semibold text-rose-800 transition-opacity duration-500" role="alert">
                        {{ $displayAuthErrorMessage }}
                    </div>
                @endif
                @if(session('status'))
                    <div class="mb-4 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                        <span>{{ session('status') }}</span>
                        <button type="button" onclick="this.parentElement.remove()" class="rounded p-1 hover:bg-emerald-100" aria-label="Dismiss">&times;</button>
                    </div>
                @endif
                <form method="POST" action="{{ route('login') }}" id="login-form" class="space-y-4"
                    @if(\App\Support\Recaptcha::shouldLoadClient()) data-recaptcha-site-key="{{ config('services.recaptcha.v3.site_key') }}" @endif>
                    @csrf
                    <input type="hidden" name="for" value="super-admin">
                    @if(\App\Support\Recaptcha::shouldLoadClient())
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                    @endif
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="example@email.com" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required autofocus>
                    </div>
                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="remember" id="remember" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                            <label for="remember" class="text-sm text-slate-600">Remember me</label>
                        </div>
                        <a href="{{ route('password.request', ['for' => 'super-admin']) }}" class="text-sm font-medium text-teal-600 hover:text-teal-700 hover:underline">Forgot password?</a>
                    </div>
                    <button type="submit" id="login-submit" class="w-full rounded-xl bg-teal-600 px-4 py-3 font-semibold text-white shadow-lg shadow-teal-600/25 transition hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">Login</button>
                </form>

                @if($tenants->isNotEmpty())
                    <div class="mt-2 hidden rounded-xl bg-slate-50/60 p-4 ring-1 ring-slate-200">
                        <p class="text-sm font-semibold text-slate-800">Sign in at your barangay</p>
                        <p class="mt-1 text-xs text-slate-600">Choose a barangay, then pick Resident or Staff/Nurse.</p>
                        <div class="mt-3 flex flex-col gap-3">
                            @foreach($tenants as $t)
                                @php $domain = $t->domains->first()?->domain; @endphp
                                @if($domain)
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            @php
                                                $hostOnly = explode(':', (string) $domain)[0];
                                                $firstLabel = explode('.', $hostOnly)[0] ?? '';
                                                $barangayDisplay = ucwords(str_replace('-', ' ', $firstLabel));
                                            @endphp
                                            <div class="truncate text-sm font-semibold text-slate-800">{{ $barangayDisplay }}</div>
                                            <div class="truncate text-xs text-slate-500">{{ $domain }}</div>
                                        </div>
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <a
                                                href="{{ $scheme . '://' . $domain . $portSuffix . '/login?for=resident' }}"
                                                class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-700"
                                            >
                                                Resident
                                            </a>
                                            <a
                                                href="{{ $scheme . '://' . $domain . $portSuffix . '/login?for=tenant' }}"
                                                class="inline-flex items-center justify-center rounded-lg border border-teal-600/30 bg-white px-3 py-1.5 text-xs font-semibold text-teal-800 shadow-sm transition hover:bg-teal-50 hover:border-teal-600/40"
                                            >
                                                Staff / Nurse
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(config('services.google.client_id'))
                    <div class="relative my-4">
                        <span class="relative flex justify-center text-xs text-slate-400"><span class="bg-white px-2">OR</span></span>
                    </div>
                    <a href="{{ route('auth.google.redirect', ['for' => 'super-admin', 'intent' => 'login']) }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Sign in with Google
                    </a>
                @endif

                @if(Route::has('sign-up'))
                    <p class="mt-4 text-center text-sm text-slate-600">Resident or staff? <a href="{{ route('sign-up') }}" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">Sign up</a> (choose your barangay and role).</p>
                    <a href="{{ route('sign-up') }}" class="mt-3 flex w-full items-center justify-center rounded-xl bg-teal-50 px-4 py-2.5 text-sm font-semibold text-teal-800 shadow-sm ring-1 ring-teal-500/10 transition hover:bg-teal-100">
                        Create an account
                    </a>
                @endif
                @if(Route::has('tenant-applications.create'))
                    <a href="{{ route('tenant-applications.create') }}" class="mt-3 flex w-full items-center justify-center rounded-xl border border-teal-600/25 bg-white px-4 py-2.5 text-sm font-semibold text-teal-800 shadow-sm transition hover:bg-teal-50">
                        Apply for tenant
                    </a>
                @endif
            </div>
        </div>
    </div>
    @include('components.professional-alerts')
    <script>
    (function () {
        var body = document.body;
        var scopeAlert = body ? body.dataset.authScopeAlert : '';
        var firstError = body ? body.dataset.authFirstError : '';

        function showAuthToast(message) {
            if (!message || typeof message !== 'string' || typeof window.showToast !== 'function') {
                return;
            }
            window.showToast(message, 'error', 9000);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                showAuthToast(scopeAlert || firstError);
                clearAuthErrorFromUrl();
            });
            return;
        }

        showAuthToast(scopeAlert || firstError);
        clearAuthErrorFromUrl();

        function clearAuthErrorFromUrl() {
            if (typeof window.history.replaceState !== 'function') {
                return;
            }

            var currentUrl = new URL(window.location.href);
            var params = currentUrl.searchParams;
            var hadAuthParams = params.has('auth_error') || params.has('oauth_flash');
            if (!hadAuthParams) {
                return;
            }

            params.delete('auth_error');
            params.delete('oauth_flash');
            currentUrl.search = params.toString();
            var nextUrl = currentUrl.pathname + (currentUrl.search ? '?' + currentUrl.search : '') + currentUrl.hash;
            window.history.replaceState({}, document.title, nextUrl);
        }
    })();
    </script>
    <script>
    (function () {
        function dismissAuthAlerts() {
            var alerts = document.querySelectorAll('[data-auth-alert]');
            alerts.forEach(function (alertNode) {
                setTimeout(function () {
                    alertNode.classList.add('opacity-0');
                    setTimeout(function () {
                        if (alertNode.parentNode) {
                            alertNode.parentNode.removeChild(alertNode);
                        }
                    }, 500);
                }, 10000);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', dismissAuthAlerts);
            return;
        }

        dismissAuthAlerts();
    })();
    </script>
    @if(\App\Support\Recaptcha::shouldLoadClient())
    <script>
    (function(){
        var form = document.getElementById('login-form');
        var tokenInput = form && document.getElementById('recaptcha_token');
        var submitBtn = form && document.getElementById('login-submit');
        var siteKey = form ? form.getAttribute('data-recaptcha-site-key') : '';
        var isSubmitting = false;
        var tokenPreloaded = false;

        function doSubmit(){
            if (isSubmitting) { return; }
            isSubmitting = true;
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Logging in...'; }
            form.removeEventListener('submit', submitHandler);
            // Use native submit to avoid re-triggering our submit handler.
            setTimeout(function(){ form.submit(); }, 50);
        }

        function waitForRecaptcha(callback) {
            if (! (form && siteKey)) { return; }

            if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function') {
                callback();
                return;
            }

            // Fast polling until grecaptcha is ready (so we don't delay on click).
            var attempts = 0;
            var maxAttempts = 60; // 60 * 50ms = 3s
            function check() {
                if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function') {
                    callback();
                    return;
                }
                if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(check, 50);
                }
            }
            check();
        }

        // Preload token for faster submit.
        function preloadToken() {
            waitForRecaptcha(function(){
                if (tokenPreloaded) { return; }
                if (! tokenInput || ! tokenInput.value) {
                    grecaptcha.ready(function(){
                        grecaptcha.execute(siteKey, { action: 'login' }).then(function(token){
                            if (tokenInput && token) {
                                tokenInput.value = token;
                                tokenPreloaded = true;
                            }
                        }).catch(function(){ /* ignore preload failure */ });
                    });
                }
            });
        }

        var submitHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (isSubmitting) { return; }

            // If token already exists (preloaded), submit immediately.
            if (tokenInput && tokenInput.value) { doSubmit(); return; }

            // Otherwise, generate one right now.
            waitForRecaptcha(function(){
                if (! tokenInput) { return; }
                grecaptcha.ready(function(){
                    grecaptcha.execute(siteKey, { action: 'login' }).then(function(token){
                        if (tokenInput && token) tokenInput.value = token;
                        doSubmit();
                    }).catch(function(){
                        isSubmitting = false;
                        tokenPreloaded = false;
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Login'; }
                    });
                });
            });
        };

        // Attach immediately; grecaptcha may load later.
        if (form && siteKey) {
            form.addEventListener('submit', submitHandler);
        }

        preloadToken();
    })();
    </script>
    @endif
</body>
</html>
