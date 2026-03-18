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
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Central Login – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.v3.site_key') }}" async defer></script>
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    <style>
        .login-panel-left { background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%); }
        @media (max-width: 767px) { .login-panel-left { min-height: 12rem; } }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden antialiased" style="font-family: 'DM Sans', ui-sans-serif, sans-serif;">
    <div class="min-h-screen overflow-visible flex items-center justify-center p-4 {{ $loginBgClass }}" @if($loginBgStyle) style="{{ $loginBgStyle }}" @endif>
        <div class="w-full max-w-4xl rounded-2xl overflow-hidden shadow-2xl ring-1 ring-slate-300/50 flex flex-col md:flex-row bg-white">
            <div class="login-panel-left md:w-[44%] flex flex-col items-center justify-center p-8 md:p-12 text-white">
                @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ config('bhcas.name') }}" class="max-w-full h-auto max-h-48 md:max-h-56 w-auto object-contain" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                <div class="hidden text-center">
                    <span class="text-2xl md:text-3xl font-bold tracking-tight">{{ config('bhcas.acronym') }}</span>
                    <p class="mt-1 text-sm md:text-base text-white/90">{{ config('bhcas.name') }}</p>
                </div>
                @else
                <div class="text-center">
                    <span class="text-2xl md:text-3xl font-bold tracking-tight">{{ config('bhcas.acronym') }}</span>
                    <p class="mt-1 text-sm md:text-base text-white/90">{{ config('bhcas.name') }}</p>
                </div>
                @endif
                <p class="mt-6 text-sm text-white/80 text-center max-w-xs">Central app – platform administration only.</p>
            </div>
            <div class="flex-1 p-6 sm:p-8 md:p-10 flex flex-col justify-center">
                <div class="mb-6">
                    <p class="text-slate-500 text-sm">Central app</p>
                    <h1 class="text-2xl font-bold text-slate-800 mt-0.5">Super Admin Login</h1>
                    <p class="text-slate-500 text-xs mt-0.5">Platform administrator access. Staff or residents? Use your barangay’s own website to log in.</p>
                </div>
                @if($errors->any())
                    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">{{ $errors->first() }}</div>
                @endif
                @if(session('status'))
                    <div class="mb-4 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                        <span>{{ session('status') }}</span>
                        <button type="button" onclick="this.parentElement.remove()" class="rounded p-1 hover:bg-emerald-100" aria-label="Dismiss">&times;</button>
                    </div>
                @endif
                <form method="POST" action="{{ route('login') }}" id="login-form" class="space-y-4"
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug')) data-recaptcha-site-key="{{ config('services.recaptcha.v3.site_key') }}" @endif>
                    @csrf
                    <input type="hidden" name="for" value="super-admin">
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
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
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
                        <p class="text-center text-xs text-slate-400">Protected by reCAPTCHA</p>
                    @endif
                </form>
            </div>
        </div>
    </div>
    @include('components.professional-alerts')
    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
    <script>
    (function(){
        var form = document.getElementById('login-form');
        var tokenInput = form && document.getElementById('recaptcha_token');
        var submitBtn = form && document.getElementById('login-submit');
        var siteKey = form ? form.getAttribute('data-recaptcha-site-key') : '';
        function doSubmit(){ if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Logging in...'; } form.removeEventListener('submit', arguments.callee); setTimeout(function(){ (form.requestSubmit && form.requestSubmit()) || form.submit(); }, 50); }
        if (form && siteKey && typeof grecaptcha !== 'undefined' && grecaptcha.ready) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (tokenInput && tokenInput.value) { doSubmit(); return; }
                grecaptcha.ready(function() {
                    grecaptcha.execute(siteKey, { action: 'login' }).then(function(token) {
                        if (tokenInput && token) tokenInput.value = token;
                        doSubmit();
                    }).catch(function() { if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Login'; } });
                });
            });
        }
    })();
    </script>
    @endif
</body>
</html>
