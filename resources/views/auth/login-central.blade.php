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
        /* Smooth animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        /* Custom scrollbar for tenant list */
        .tenant-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .tenant-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .tenant-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        .tenant-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        /* Left panel gradient override for custom backgrounds */
        .login-panel-left {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%);
        }
        .login-central-outer {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 40%, #0891b2 100%);
        }
        @media (max-width: 767px) {
            .login-panel-left {
                min-height: 14rem;
            }
        }
        /* Improved focus states */
        .focus-ring-custom:focus {
            outline: 2px solid #0d9488;
            outline-offset: 2px;
        }
        /* Keep reCAPTCHA badge at bottom-right, collapsed by default, expand on hover. */
        .grecaptcha-badge {
            position: fixed !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 2147483000 !important;
            bottom: 10px !important;
            right: 10px !important;
            left: auto !important;
            top: auto !important;
            transform: none !important;
            width: 70px !important;
            overflow: hidden !important;
            transition: width 0.2s ease !important;
        }
        .grecaptcha-badge:hover,
        .grecaptcha-badge:focus-within {
            width: 256px !important;
        }
        .grecaptcha-badge iframe {
            visibility: visible !important;
            opacity: 1 !important;
        }
    </style>
</head>
<body
    class="min-h-screen overflow-x-hidden antialiased"
    style="font-family: 'DM Sans', ui-sans-serif, sans-serif;"
    data-auth-scope-alert="{{ (string) ($authScopeAlertMessage ?? '') }}"
    data-auth-first-error="{{ (string) ($displayAuthErrorMessage ?? '') }}"
>
    <div class="min-h-screen overflow-visible flex items-center justify-center p-4 {{ $loginBgOuterClass }} {{ $loginOuterExtraClass }}" {!! $loginBgStyleAttr !!}>
        <!-- Main card with glassmorphism -->
        <div class="w-full max-w-5xl rounded-3xl overflow-hidden shadow-2xl ring-1 ring-white/20 flex flex-col md:flex-row bg-white/95 backdrop-blur-xl backdrop-saturate-150">
            
            <!-- Left panel: Logo + Tenant quick links -->
            <div class="login-panel-left md:w-[42%] flex flex-col items-center justify-center p-6 md:p-10 text-white relative overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute inset-0 bg-gradient-to-br from-teal-600/20 to-cyan-600/20"></div>
                <div class="absolute -top-20 -left-20 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-20 -right-20 h-40 w-40 rounded-full bg-cyan-300/10 blur-2xl"></div>
                
                <div class="relative w-full max-w-xs">
                    <!-- Logo Card -->
                    <div class="rounded-2xl bg-white/10 p-6 md:p-8 shadow-xl ring-1 ring-white/20 backdrop-blur-sm transition-all duration-300 hover:bg-white/15">
                        <div class="flex flex-col items-center justify-center text-center">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ config('bhcas.name') }}" class="max-w-full h-auto max-h-36 md:max-h-44 w-auto object-contain drop-shadow-lg" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                                <div class="hidden">
                                    <p class="text-white text-base md:text-lg font-bold leading-tight">{{ config('bhcas.name') }}</p>
                                    <div class="mt-4 flex justify-center gap-2 flex-wrap" aria-hidden="true">
                                        <span class="inline-flex rounded-lg bg-white/20 p-2 text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                                        <span class="inline-flex rounded-lg bg-white/20 p-2 text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></span>
                                        <span class="inline-flex rounded-lg bg-white/20 p-2 text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                                        <span class="inline-flex rounded-lg bg-white/20 p-2 text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
                                        <span class="inline-flex rounded-lg bg-white/20 p-2 text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                                    </div>
                                </div>
                            @else
                                <p class="text-white text-base md:text-lg font-bold leading-tight">{{ config('bhcas.name') }}</p>
                                <div class="mt-4 flex justify-center gap-2 flex-wrap" aria-hidden="true">
                                    <span class="inline-flex rounded-lg bg-white/20 p-2 text-white" title="Records"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                                    <span class="inline-flex rounded-lg bg-white/20 p-2 text-white" title="Home"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></span>
                                    <span class="inline-flex rounded-lg bg-white/20 p-2 text-white" title="Calendar"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                                    <span class="inline-flex rounded-lg bg-white/20 p-2 text-white" title="Health"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
                                    <span class="inline-flex rounded-lg bg-white/20 p-2 text-white" title="Mobile"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($tenants->isNotEmpty())
                        <!-- Tenant quick access list -->
                        <div class="mt-6 rounded-2xl bg-white/10 p-5 ring-1 ring-white/20 backdrop-blur-sm">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-teal-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <p class="text-sm font-semibold text-white">Sign in at your barangay</p>
                            </div>
                            <p class="text-xs text-white/70 mb-4">Select a barangay, then choose Resident or Staff.</p>

                            <div class="max-h-[380px] overflow-y-auto pr-1 tenant-scroll space-y-2.5">
                                @foreach($tenants as $t)
                                    @php $domain = $t->domains->first()?->domain; @endphp
                                    @if($domain)
                                        @php
                                            $hostOnly = explode(':', (string) $domain)[0];
                                            $firstLabel = explode('.', $hostOnly)[0] ?? '';
                                            $barangayDisplay = ucwords(str_replace('-', ' ', $firstLabel));
                                            $residentLoginUrl = $scheme . '://' . $domain . $portSuffix . '/login?for=resident';
                                            $staffLoginUrl = $scheme . '://' . $domain . $portSuffix . '/login?for=tenant';
                                            $tenantLogoPath = (string) ($t->logo_path ?? '');
                                            $tenantLogoUrl = $tenantLogoPath !== ''
                                                ? (str_contains($tenantLogoPath, 'cloudinary.com') ? $tenantLogoPath : asset('storage/' . $tenantLogoPath))
                                                : null;
                                        @endphp
                                        <div
                                            class="rounded-xl bg-white/5 p-3.5 ring-1 ring-white/10 transition-all duration-200 hover:bg-white/10 hover:ring-white/20 hover:shadow-lg cursor-pointer group"
                                            role="link"
                                            tabindex="0"
                                            onclick="window.location.href='{{ $residentLoginUrl }}'"
                                            onkeydown="if(event.key==='Enter' || event.key===' '){ window.location.href='{{ $residentLoginUrl }}'; }"
                                        >
                                            <div class="flex items-center gap-2.5">
                                                @if($tenantLogoUrl !== null)
                                                    <img
                                                        src="{{ $tenantLogoUrl }}"
                                                        alt="{{ $barangayDisplay }} logo"
                                                        class="h-8 w-8 shrink-0 rounded-full object-cover ring-1 ring-white/30"
                                                        loading="lazy"
                                                        onerror="this.style.display='none';"
                                                    >
                                                @endif
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-semibold text-white group-hover:text-teal-100">{{ $barangayDisplay }}</div>
                                                    <div class="truncate text-xs text-white/60">{{ $domain }}</div>
                                                </div>
                                            </div>

                                            <div class="mt-3 flex gap-2">
                                                <a
                                                    href="{{ $residentLoginUrl }}"
                                                    onclick="event.stopPropagation()"
                                                    class="flex-1 inline-flex items-center justify-center rounded-lg bg-teal-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition-all duration-200 hover:bg-teal-700 hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-teal-400"
                                                >
                                                    Resident
                                                </a>
                                                <a
                                                    href="{{ $staffLoginUrl }}"
                                                    onclick="event.stopPropagation()"
                                                    class="flex-1 inline-flex items-center justify-center rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-xs font-semibold text-white shadow-sm transition-all duration-200 hover:bg-white/20 hover:border-white/50 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-white/50"
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
            </div>

            <!-- Right panel: Login form -->
            <div class="flex-1 p-8 md:p-10 flex flex-col justify-center bg-white">
                <div class="max-w-md w-full mx-auto">
                    <div class="mb-8">
                        <span class="inline-block rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-teal-800 ring-1 ring-teal-200">
                            Central Administration
                        </span>
                        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">Central App Login</h1>
                        <p class="mt-2 text-sm text-slate-600">
                            Platform administrator access. Staff or residents should sign in through their barangay portal.
                        </p>
                    </div>

                    @if($displayAuthErrorMessage)
                        <div data-auth-alert class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 shadow-sm transition-opacity duration-500" role="alert">
                            <div class="flex items-start gap-3">
                                <svg class="h-5 w-5 shrink-0 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $displayAuthErrorMessage }}</span>
                            </div>
                        </div>
                    @endif

                    @if(session('status'))
                        <div class="mb-6 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm ring-1 ring-emerald-200">
                            <div class="flex items-center gap-3">
                                <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ session('status') }}</span>
                            </div>
                            <button type="button" onclick="this.parentElement.remove()" class="rounded-lg p-1 hover:bg-emerald-100 transition" aria-label="Dismiss">&times;</button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="login-form" class="space-y-5"
                        @if(\App\Support\Recaptcha::shouldLoadClient()) data-recaptcha-site-key="{{ config('services.recaptcha.v3.site_key') }}" @endif>
                        @csrf
                        <input type="hidden" name="for" value="super-admin">
                        @if(\App\Support\Recaptcha::shouldLoadClient())
                            <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                        @endif

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 7.89a2 2 0 002.34 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="admin@example.com" 
                                       class="w-full rounded-xl border-slate-300 bg-slate-50/80 pl-10 pr-4 py-3 text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:bg-white" required autofocus>
                            </div>
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="password" name="password" id="password" placeholder="••••••••" 
                                       class="w-full rounded-xl border-slate-300 bg-slate-50/80 pl-10 pr-4 py-3 text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:bg-white" required>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="remember" id="remember" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                <label for="remember" class="text-sm text-slate-600">Remember me</label>
                            </div>
                            <a href="{{ route('password.request', ['for' => 'super-admin']) }}" class="text-sm font-medium text-teal-600 hover:text-teal-700 hover:underline transition">
                                Forgot password?
                            </a>
                        </div>

                        <button type="submit" id="login-submit" 
                                class="relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-teal-600 to-teal-700 px-4 py-3.5 font-semibold text-white shadow-lg shadow-teal-500/25 transition-all duration-300 hover:shadow-xl hover:shadow-teal-500/30 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                            <span class="relative">Sign in to Central App</span>
                        </button>
                    </form>

                    @if(config('services.google.client_id'))
                        <div class="relative my-5">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-slate-200"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="bg-white px-3 text-slate-500">Or continue with</span>
                            </div>
                        </div>

                        <a href="{{ route('auth.google.redirect', ['for' => 'super-admin', 'intent' => 'login']) }}" 
                           class="flex w-full items-center justify-center gap-3 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition-all duration-200 hover:bg-slate-50 hover:shadow-md hover:border-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Sign in with Google
                        </a>
                    @endif

                    <div class="mt-6 space-y-3">
                        @if(Route::has('sign-up'))
                            <a href="{{ route('sign-up', ['for' => 'super-admin']) }}" 
                               class="flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:border-slate-400">
                                Register Super Admin
                            </a>
                        @endif
                        @if(Route::has('tenant-applications.create'))
                            <a href="{{ route('tenant-applications.create') }}" 
                               class="flex w-full items-center justify-center rounded-xl border border-teal-300 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800 shadow-sm transition hover:bg-teal-100 hover:border-teal-400">
                                Apply for a new tenant
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('components.professional-alerts')
    
    <!-- JavaScript for auth alerts and reCAPTCHA (unchanged) -->
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
            if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="relative">Signing in...</span>'; }
            form.removeEventListener('submit', submitHandler);
            setTimeout(function(){ form.submit(); }, 50);
        }

        function waitForRecaptcha(callback) {
            if (! (form && siteKey)) { return; }

            if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function') {
                callback();
                return;
            }

            var attempts = 0;
            var maxAttempts = 60;
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
                        }).catch(function(){ /* ignore */ });
                    });
                }
            });
        }

        var submitHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (isSubmitting) { return; }

            if (tokenInput && tokenInput.value) { doSubmit(); return; }

            waitForRecaptcha(function(){
                if (! tokenInput) { return; }
                grecaptcha.ready(function(){
                    grecaptcha.execute(siteKey, { action: 'login' }).then(function(token){
                        if (tokenInput && token) tokenInput.value = token;
                        doSubmit();
                    }).catch(function(){
                        isSubmitting = false;
                        tokenPreloaded = false;
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<span class="relative">Sign in to Central App</span>'; }
                    });
                });
            });
        };

        if (form && siteKey) {
            form.addEventListener('submit', submitHandler);
        }

        preloadToken();
    })();
    </script>
    @endif
</body>
</html>