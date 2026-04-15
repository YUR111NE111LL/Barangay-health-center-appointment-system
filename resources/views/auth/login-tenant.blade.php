@php
    $pageTitle = 'Tenant Login';
    $subtitle = 'Sign in with your barangay account.';
    $logoPath = $tenant->logo_path ? asset($tenant->logo_path) : null;
    $loginBg = config('bhcas.login_background', 'teal');
    $loginBgColor = $tenant->primary_color ?: config('bhcas.login_background_color');
    if ($loginBg === 'custom' && $loginBgColor) {
        $loginBgClass = '';
        $loginBgStyle = 'background: linear-gradient(135deg, ' . e($tenant->getPrimaryColor()) . ' 0%, #0f766e 50%, #115e59 100%);';
    } elseif ($loginBg === 'slate') {
        $loginBgClass = 'bg-slate-200';
        $loginBgStyle = '';
    } else {
        $loginBgClass = 'bg-gradient-to-br from-teal-500 via-teal-600 to-cyan-700';
        $loginBgStyle = '';
    }
    $outerWrapperStyleAttr = $loginBgStyle ? ' style="'.e($loginBgStyle).'"' : '';
@endphp
@php
    // Display barangay name derived from the current host (tenant domain):
    // e.g. brgy-bangcud.localhost => Brgy Bangcud
    $tenant->loadMissing('domains');
    $host = (string) request()->getHost();
    $firstLabel = explode('.', $host)[0] ?? '';
    $barangayDisplay = $firstLabel !== ''
        ? ucwords(str_replace('-', ' ', $firstLabel))
        : $tenant->barangayDisplayName();
    $authScopeAlertMessage = session('auth_scope_alert') ?: request()->query('auth_error');
    $displayAuthErrorMessage = $errors->first() ?: $authScopeAlertMessage;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} – {{ $barangayDisplay }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(\App\Support\Recaptcha::shouldLoadClient())
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.v3.site_key') }}" async defer></script>
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    <style>
        body.tenant-login-body { font-family: 'DM Sans', ui-sans-serif, sans-serif; }
        .login-panel-left.tenant-brand-gradient {
            background: linear-gradient(135deg, var(--tenant-brand, #0d9488) 0%, #0f766e 50%, #115e59 100%);
        }
        @media (max-width: 767px) { .login-panel-left { min-height: 12rem; } }
    </style>
</head>
<body
    class="tenant-login-body min-h-screen overflow-x-hidden antialiased"
    data-auth-scope-alert="{{ (string) ($authScopeAlertMessage ?? '') }}"
    data-auth-first-error="{{ (string) ($displayAuthErrorMessage ?? '') }}"
>
    <div class="min-h-screen overflow-visible flex items-center justify-center p-4 {{ $loginBgClass }}"{!! $outerWrapperStyleAttr !!}>
        <div class="w-full max-w-4xl rounded-2xl overflow-hidden shadow-2xl ring-1 ring-slate-300/50 flex flex-col md:flex-row bg-white">
            <div class="login-panel-left tenant-brand-gradient md:w-[44%] flex flex-col items-center justify-center p-8 md:p-12 text-white" style="--tenant-brand: {{ e($tenant->getPrimaryColor()) }}">
                @if($logoPath)
                <img src="{{ $logoPath }}" alt="{{ $barangayDisplay }}" class="max-w-full h-auto max-h-48 md:max-h-56 w-auto object-contain" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                <div class="hidden text-center">
                    <span class="text-2xl md:text-3xl font-bold tracking-tight">{{ $barangayDisplay }}</span>
                </div>
                @else
                <div class="text-center">
                    <span class="text-2xl md:text-3xl font-bold tracking-tight">{{ $barangayDisplay }}</span>
                    @if($tenant->tagline)<p class="mt-1 text-sm text-white/90">{{ $tenant->tagline }}</p>@endif
                </div>
                @endif
                <p class="mt-6 text-sm text-white/80 text-center max-w-xs">Book and manage health center appointments at {{ $barangayDisplay }}.</p>
            </div>
            <div class="flex-1 p-6 sm:p-8 md:p-10 flex flex-col justify-center">
                <div class="mb-6">
                    <p class="text-slate-500 text-sm">{{ $barangayDisplay }}</p>
                    <h1 class="text-2xl font-bold text-slate-800 mt-0.5">{{ $barangayDisplay }}</h1>
                    <p class="text-slate-500 text-xs mt-0.5">{{ $subtitle }}</p>
                    <p class="text-slate-400 text-[11px] mt-2 leading-snug">{{ __('Only one signed-in user per browser session. For two accounts at once, use another browser or a separate browser profile—not another tab in the same private window.') }}</p>
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
                    <input type="hidden" name="for" value="{{ $for ?? 'resident' }}">
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
                        <a href="{{ route('password.request', ['for' => $for ?? 'resident']) }}" class="text-sm font-medium text-teal-600 hover:text-teal-700 hover:underline">Forgot password?</a>
                    </div>
                    <button type="submit" id="login-submit" class="w-full rounded-xl bg-teal-600 px-4 py-3 font-semibold text-white shadow-lg shadow-teal-600/25 transition hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">Login</button>
                </form>
                @if(config('services.google.client_id'))
                    <div class="relative my-4">
                        <span class="relative flex justify-center text-xs text-slate-400"><span class="bg-white px-2">OR</span></span>
                    </div>
                    <a href="{{ route('auth.google.redirect', ['for' => $for ?? 'resident', 'tenant_id' => $tenant->id, 'intent' => 'login']) }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        {{ ($for ?? '') === 'resident' ? 'Sign in with Google' : 'Login with Google' }}
                    </a>
                @endif
                @if(Route::has('sign-up'))
                    <p class="mt-4 text-center text-sm text-slate-600">Don't have an account? <a href="{{ route('sign-up', ($for ?? '') === 'tenant' ? ['for' => 'tenant'] : []) }}" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">Sign up</a></p>
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
        function waitForRecaptcha(callback) {
            if (! (form && siteKey)) { return; }

            if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function') {
                callback();
                return;
            }

            // Fast polling until grecaptcha is ready (reduces click delay).
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

        function doSubmit(){
            if (isSubmitting) { return; }
            isSubmitting = true;
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Logging in...'; }
            form.removeEventListener('submit', submitHandler);
            // Use native submit to avoid re-triggering our submit handler.
            setTimeout(function(){ form.submit(); }, 50);
        }

        // Preload token for faster submission.
        function preloadToken() {
            waitForRecaptcha(function(){
                if (tokenPreloaded) { return; }
                if (! tokenInput || tokenInput.value) { return; }
                grecaptcha.ready(function(){
                    grecaptcha.execute(siteKey, { action: 'login' }).then(function(token){
                        if (tokenInput && token) {
                            tokenInput.value = token;
                            tokenPreloaded = true;
                        }
                    }).catch(function(){ /* ignore preload failure */ });
                });
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
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Login'; }
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
