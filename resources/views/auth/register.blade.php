@php
    $logoPath = config('bhcas.logo_path');
    $logoUrl = $logoPath ? asset($logoPath) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign up – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(\App\Support\Recaptcha::shouldProcess())
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.v3.site_key') }}" async defer></script>
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    <style>
        .signup-panel-left { background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%); }
        .signup-central-outer { background: linear-gradient(135deg, #0d9488 0%, #0f766e 40%, #0891b2 100%); }
        @media (max-width: 767px) { .signup-panel-left { min-height: 12rem; } }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden antialiased" style="font-family: 'DM Sans', ui-sans-serif, sans-serif;">
    <div class="min-h-screen overflow-visible flex items-center justify-center p-4 signup-central-outer">
        <div class="w-full max-w-4xl rounded-2xl overflow-hidden shadow-2xl ring-1 ring-slate-300/50 flex flex-col md:flex-row bg-white">
            <div class="signup-panel-left md:w-[44%] flex flex-col items-center justify-center p-8 md:p-12 text-white">
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
                </div>
                <p class="mt-6 text-sm text-white/80 text-center max-w-xs">Create an account as resident or staff. Choose your barangay and role.</p>
            </div>
            <div class="flex-1 p-6 sm:p-8 md:p-10 flex flex-col justify-center">
                <div class="mb-6">
                    <p class="text-slate-500 text-sm">Central app</p>
                    <h1 class="text-2xl font-bold text-slate-800 mt-0.5">
                        {{ request('for') === 'super-admin' ? 'Super Admin Sign up' : 'Sign up' }}
                    </h1>
                    <p class="text-slate-500 text-xs mt-0.5">
                        {{ request('for') === 'super-admin' ? 'Create your Super Admin account.' : 'Register as a resident or staff for your health center. Select your barangay and role below.' }}
                    </p>
                </div>
                @if($errors->any())
                    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                        <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('register') }}" id="register-form" class="space-y-4"
                    @if(\App\Support\Recaptcha::shouldProcess()) data-recaptcha-site-key="{{ config('services.recaptcha.v3.site_key') }}" @endif>
                    @csrf
                    @if(\App\Support\Recaptcha::shouldProcess())
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                    @endif
                    @php
                        $forParam = request('for', 'resident');
                        $isSuperAdminMode = $forParam === 'super-admin';
                        $defaultRole = $isSuperAdminMode ? 'Super Admin' : ($forParam === 'tenant' ? 'Staff' : 'Resident');
                        $roleOld = old('role', $defaultRole);
                        $currentTenantDomain = $currentTenant?->domains()->first()?->domain;
                        $currentTenantLabel = $currentTenantDomain
                            ? str($currentTenantDomain)->before('.')->replace('-', ' ')->title()->toString()
                            : ($currentTenant?->site_name ?: 'Current Barangay');
                    @endphp
                    <div>
                        <label for="role" class="mb-1 block text-sm font-medium text-slate-700">I am signing up as <span class="text-rose-500">*</span></label>
                        <select name="role" id="role" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                            <option value="Resident" {{ $roleOld === 'Resident' ? 'selected' : '' }}>Resident (Patient)</option>
                            <option value="Staff" {{ $roleOld === 'Staff' ? 'selected' : '' }}>Staff</option>
                            <option value="Nurse" {{ $roleOld === 'Nurse' ? 'selected' : '' }}>Nurse / Midwife</option>
                            <option value="Health Center Admin" {{ $roleOld === 'Health Center Admin' ? 'selected' : '' }}>Barangay / Health Center Admin</option>
                            @if(empty($currentTenant))
                                <option value="Super Admin" {{ $roleOld === 'Super Admin' ? 'selected' : '' }}>Super Admin</option>
                            @endif
                        </select>
                    </div>
                    @if(!empty($currentTenant))
                        <input type="hidden" name="tenant_id" id="tenant_id" value="{{ $currentTenant->id }}">
                        <div class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm text-slate-700">
                            Barangay: <strong>{{ $currentTenantLabel }}</strong>
                            @if($currentTenantDomain)
                                <span class="ml-1 text-slate-500">({{ $currentTenantDomain }})</span>
                            @endif
                        </div>
                    @else
                        @if($isSuperAdminMode)
                            {{-- Super Admin accounts are tenant-less --}}
                            <input type="hidden" name="tenant_id" id="tenant_id" value="">
                        @else
                            <div id="tenant_id-wrap">
                                <label for="tenant_id" class="mb-1 block text-sm font-medium text-slate-700">Health Center / Barangay <span class="text-rose-500">*</span></label>
                                <select name="tenant_id" id="tenant_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <option value="">Select...</option>
                                    @foreach($tenants as $t)
                                        @php
                                            $tenantDomain = $t->domains->first()?->domain;
                                            $tenantLabel = $tenantDomain
                                                ? str($tenantDomain)->before('.')->replace('-', ' ')->title()->toString()
                                                : ($t->site_name ?: 'Tenant #'.$t->id);
                                        @endphp
                                        <option value="{{ $t->id }}" {{ old('tenant_id') == $t->id ? 'selected' : '' }}>
                                            {{ $tenantLabel }}@if($tenantDomain) ({{ $tenantDomain }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endif
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Full name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                    </div>
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                    </div>
                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" id="password" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                    </div>
                    <button type="submit" id="register-submit" class="w-full rounded-xl bg-teal-600 px-4 py-3 font-semibold text-white shadow-lg shadow-teal-600/30 transition hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        Sign up
                    </button>
                    @if(\App\Support\Recaptcha::shouldProcess())
                        <p class="text-center text-xs text-slate-400">Protected by reCAPTCHA</p>
                    @endif
                    @if(config('services.google.client_id'))
                        <div id="google-signup-wrap" class="relative my-4">
                            <span class="relative flex justify-center text-xs text-slate-400"><span class="bg-white px-2">OR</span></span>
                        </div>
                        <a href="#" id="google-signup-btn" data-google-redirect-url="{{ route('auth.google.redirect', ['for' => $isSuperAdminMode ? 'super-admin' : 'resident', 'intent' => 'signup']) }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                            Sign up with Google
                        </a>
                    @endif
                </form>
                <p class="mt-4 text-center text-sm text-slate-600">Already have an account? Sign in:</p>
                <p class="mt-1 text-center text-sm text-slate-600">
                    @if(!empty($currentTenant))
                        <a href="{{ route('login', ['for' => 'resident']) }}" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">Resident</a>
                        <span class="text-slate-400 mx-1">|</span>
                        <a href="{{ route('login', ['for' => 'tenant']) }}" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">Staff / Nurse</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">Super Admin</a>
                        <span class="text-slate-400 mx-1">|</span>
                        <span class="text-slate-600">Resident / Staff: sign in at</span>
                    @endif
                </p>
                @if(empty($currentTenant))
                    <a
                        href="{{ route('sign-up', ['for' => 'super-admin']) }}"
                        class="mt-3 flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                    >
                        Super Admin Sign up
                    </a>
                    <a
                        href="http://127.0.0.1:8000/login"
                        class="mt-2 flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50"
                    >
                        Central App
                    </a>
                @else
                    <a
                        href="{{ route('sign-up', ['for' => 'super-admin']) }}"
                        class="mt-3 flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                    >
                        Super Admin Sign up
                    </a>
                    <a
                        href="{{ route('login', ['for' => 'super-admin']) }}"
                        class="mt-2 flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50"
                    >
                        Super Admin Login (Central)
                    </a>
                @endif
            </div>
        </div>
    </div>
    @if(\App\Support\Recaptcha::shouldProcess())
    <script>
    (function() {
        var form = document.getElementById('register-form');
        var submitBtn = document.getElementById('register-submit');
        var siteKey = form ? form.getAttribute('data-recaptcha-site-key') : '';
        var tokenInput = document.getElementById('recaptcha_token');
        var allowSubmit = false;
        var tokenGenerating = false;
        
        // Fast check for reCAPTCHA - check immediately, then poll quickly
        function waitForRecaptcha(callback) {
            // Check immediately if already loaded
            if (typeof grecaptcha !== 'undefined') {
                callback();
                return;
            }
            
            // Fast polling: 20ms intervals, max 2 seconds (100 attempts)
            var attempts = 0;
            var maxAttempts = 100;
            function check() {
                if (typeof grecaptcha !== 'undefined') {
                    callback();
                } else if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(check, 20);
                } else {
                    // Fallback: show error
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Sign up';
                    }
                    console.warn('reCAPTCHA took too long to load');
                    showToast('reCAPTCHA failed to load. Please refresh the page.', 'error', 8000);
                }
            }
            check();
        }
        
        // Pre-load token when page loads for faster submission
        function preloadToken() {
            waitForRecaptcha(function() {
                if (typeof grecaptcha !== 'undefined' && siteKey) {
                    grecaptcha.ready(function() {
                        grecaptcha.execute(siteKey, { action: 'register' }).then(function(token) {
                            if (tokenInput && token) {
                                tokenInput.value = token;
                            }
                        }).catch(function(error) {
                            console.error('reCAPTCHA preload error:', error);
                        });
                    });
                }
            });
        }
        
        // Function to actually submit the form
        function doSubmit() {
            // Remove the event listener completely
            form.removeEventListener('submit', submitHandler);
            // Submit the form directly - this bypasses the event listener
            setTimeout(function() {
                form.submit();
            }, 100);
        }
        
        var submitHandler = function(e) {
            // Always prevent default first
            e.preventDefault();
            e.stopPropagation();
            
            // If token already exists, submit immediately
            if (tokenInput && tokenInput.value) {
                doSubmit();
                return;
            }
            
            // If already generating, wait
            if (tokenGenerating) {
                return;
            }
            
            tokenGenerating = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Verifying...';
            }
            
            // Get fresh token quickly
            waitForRecaptcha(function() {
                if (typeof grecaptcha === 'undefined') {
                    tokenGenerating = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Sign up';
                    }
                    showToast('reCAPTCHA failed to load. Please refresh the page.', 'error', 8000);
                    return;
                }
                
                grecaptcha.ready(function() {
                    grecaptcha.execute(siteKey, { action: 'register' }).then(function(token) {
                        if (tokenInput && token) {
                            tokenInput.value = token;
                        }
                        tokenGenerating = false;
                        // Submit the form
                        doSubmit();
                    }).catch(function(error) {
                        console.error('reCAPTCHA error:', error);
                        tokenGenerating = false;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Sign up';
                        }
                        showToast('reCAPTCHA verification failed. Please try again.', 'error');
                    });
                });
            });
        };
        
        // Start preloading immediately
        if (form && siteKey) {
            preloadToken();
            form.addEventListener('submit', submitHandler);
        }
    })();
    </script>
    @endif
    <script>
    (function() {
        var roleSel = document.getElementById('role');
        var googleWrap = document.getElementById('google-signup-wrap');
        var googleBtn = document.getElementById('google-signup-btn');
        function updateForRole() {
            var role = roleSel ? roleSel.value : '';
            var isResident = role === 'Resident';
            if (googleWrap) googleWrap.style.display = isResident ? 'block' : 'none';
            if (googleBtn) googleBtn.style.display = isResident ? 'flex' : 'none';
        }
        if (roleSel) {
            roleSel.addEventListener('change', updateForRole);
            updateForRole();
        }
    })();
</script>
    @if(config('services.google.client_id'))
    <script>
    (function() {
        var btn = document.getElementById('google-signup-btn');
        if (!btn) return;
        var baseUrl = btn.getAttribute('data-google-redirect-url') || '';
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var sel = document.getElementById('tenant_id');
            var tid = sel ? sel.value : '';
            if (!tid) { showToast('Please select your barangay first.', 'warning'); if (sel) sel.focus(); return; }
            var url = baseUrl + (baseUrl.indexOf('?') !== -1 ? '&' : '?') + 'tenant_id=' + encodeURIComponent(tid);
            window.location.href = url;
        });
    })();
    </script>
    @endif
    @include('components.professional-alerts')
</body>
</html>
