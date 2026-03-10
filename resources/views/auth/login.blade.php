   @php
    $pageTitle = 'Login';
    $subtitle = '';
    $showRegister = true;
    if (($for ?? '') === 'super-admin') {
        $pageTitle = 'Super Admin Login';
        $subtitle = 'Platform administrator access.';
        $showRegister = false;
    } elseif (($for ?? '') === 'tenant') {
        $pageTitle = 'Staff & Nurse Login';
        $subtitle = 'For health center admins, nurses/midwives, and barangay staff. Select your barangay, then sign in.';
        $showRegister = false;
    } else {
        $pageTitle = 'Resident Login';
        $subtitle = 'Book and manage your appointments. Use the email you registered with at your barangay.';
        $showRegister = true;
    }
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
    <title>{{ $pageTitle }} – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.v3.site_key') }}" async defer></script>
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
    <style>
        .login-panel-left { background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%); }
        @media (max-width: 767px) {
            .login-panel-left { min-height: 12rem; }
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden antialiased" style="font-family: 'DM Sans', ui-sans-serif, sans-serif;">
    <div class="min-h-screen overflow-visible flex items-center justify-center p-4 {{ $loginBgClass }}" @if($loginBgStyle) style="{{ $loginBgStyle }}" @endif>
        <div class="w-full max-w-4xl rounded-2xl overflow-hidden shadow-2xl ring-1 ring-slate-300/50 flex flex-col md:flex-row bg-white">
            {{-- Left: Logo / branding --}}
            <div class="login-panel-left md:w-[44%] flex flex-col items-center justify-center p-8 md:p-12 text-white">
                @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ config('bhcas.name') }}" class="max-w-full h-auto max-h-48 md:max-h-56 w-auto object-contain" onerror="this.style.display='none'; var f=this.nextElementSibling; if(f){ f.classList.remove('hidden'); f.style.display='block'; }">
                <div class="hidden text-center" style="display:none;">
                    <span class="text-2xl md:text-3xl font-bold tracking-tight">{{ config('bhcas.acronym') }}</span>
                    <p class="mt-1 text-sm md:text-base text-white/90">{{ config('bhcas.name') }}</p>
                </div>
                @else
                <div class="text-center">
                    <span class="text-2xl md:text-3xl font-bold tracking-tight">{{ config('bhcas.acronym') }}</span>
                    <p class="mt-1 text-sm md:text-base text-white/90">{{ config('bhcas.name') }}</p>
                </div>
                @endif
                <p class="mt-6 text-sm text-white/80 text-center max-w-xs">Book and manage health center appointments in your barangay.</p>
            </div>

            {{-- Right: Form --}}
            <div class="flex-1 p-6 sm:p-8 md:p-10 flex flex-col justify-center">
                <div class="mb-6">
                    <p class="text-slate-500 text-sm">Welcome to</p>
                    <h1 class="text-2xl font-bold text-slate-800 mt-0.5">{{ config('bhcas.name') }}</h1>
                    <p class="text-slate-600 text-sm mt-1">{{ $pageTitle }}</p>
                    @if($subtitle)
                        <p class="text-slate-500 text-xs mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>

                @if($errors->any())
                    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                        {{ $errors->first() }}
                    </div>
                @endif
                @if(session('status'))
                    <div class="mb-4 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                        <span>{{ session('status') }}</span>
                        <button type="button" onclick="this.parentElement.remove()" class="rounded p-1 hover:bg-emerald-100" aria-label="Dismiss">&times;</button>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="login-form" class="space-y-4"
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug')) data-recaptcha-site-key="{{ config('services.recaptcha.v3.site_key') }}" @endif
                    @if($errors->has('tenant_id')) data-tenant-error="{{ e($errors->first('tenant_id')) }}" @endif>
                    @csrf
                    @if(!empty($for))
                        <input type="hidden" name="for" value="{{ $for }}">
                    @endif
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                    @endif
                    @if(in_array($for ?? '', ['tenant', 'resident']) && $tenants->isNotEmpty())
                        <div>
                            <label for="tenant_id" class="mb-1 block text-sm font-medium text-slate-700">Select your barangay <span class="text-rose-500">*</span></label>
                            <select name="tenant_id" id="tenant_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required autofocus>
                                <option value="">— Choose barangay —</option>
                                @foreach($tenants as $t)
                                    <option value="{{ $t->id }}" {{ (string) old('tenant_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                            @error('tenant_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-slate-500">You must log in under the barangay where you are registered.</p>
                        </div>
                    @endif
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="example@email.com" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required {{ ($for ?? '') === 'super-admin' ? 'autofocus' : '' }}>
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
                    <button type="submit" id="login-submit" class="w-full rounded-xl bg-teal-600 px-4 py-3 font-semibold text-white shadow-lg shadow-teal-600/25 transition hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        Login
                    </button>
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
                        <p class="text-center text-xs text-slate-400">Protected by reCAPTCHA</p>
                    @endif
                </form>

                @if(config('services.google.client_id'))
                    <div class="relative my-4">
                        <span class="relative flex justify-center text-xs text-slate-400"><span class="bg-white px-2">OR</span></span>
                    </div>
                    <a href="#" id="google-login-btn" data-for="{{ $for ?? 'resident' }}" data-google-redirect-url="{{ route('auth.google.redirect', ['for' => $for ?? 'resident', 'intent' => ($for ?? '') === 'super-admin' ? 'signup' : 'login']) }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        {{ ($for ?? '') === 'super-admin' ? 'Sign in with Google' : 'Login with Google' }}
                    </a>
                @endif

                @if(($for ?? '') === 'resident' && $tenants->isNotEmpty())
                    <div class="mt-6 border-t border-slate-200 pt-4">
                        <p class="text-xs text-slate-500">Resident login depends on your barangay. Use the email you registered with at:</p>
                        <ul class="mt-1 list-inside list-disc text-xs text-slate-500">
                            @foreach($tenants as $t)<li>{{ $t->name }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                @if(Route::has('sign-up') && ($for ?? '') !== 'super-admin')
                    <p class="mt-4 text-center text-sm text-slate-600">Don't have an account? <a href="{{ route('sign-up') }}" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">Sign up</a></p>
                @endif
                <p class="mt-4 text-center text-xs text-slate-500">
                    Login as:
                    <a href="{{ route('login', ['for' => 'super-admin']) }}" class="text-teal-600 hover:underline">Super Admin</a> ·
                    <a href="{{ route('login', ['for' => 'tenant']) }}" class="text-teal-600 hover:underline">Staff / Nurse</a> ·
                    <a href="{{ route('login', ['for' => 'resident']) }}" class="text-teal-600 hover:underline">Resident</a>
                </p>
            </div>
        </div>
    </div>

    @include('components.professional-alerts')
    @if($errors->has('tenant_id'))
    <script>
    (function() {
        var form = document.getElementById('login-form');
        var msg = form && form.getAttribute('data-tenant-error');
        if (msg) showToast(msg, 'error');
    })();
    </script>
    @endif
    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
    <script>
    (function() {
        var form = document.getElementById('login-form');
        var submitBtn = document.getElementById('login-submit');
        var siteKey = form ? form.getAttribute('data-recaptcha-site-key') : '';
        var tokenInput = document.getElementById('recaptcha_token');
        var allowSubmit = false;
        var tokenGenerating = false;
        
        // Wait for reCAPTCHA to be fully loaded and ready
        function waitForRecaptcha(callback) {
            // Check if grecaptcha exists and has the ready function
            if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function') {
                callback();
                return;
            }
            
            // Fast polling: 50ms intervals, max 5 seconds (100 attempts)
            var attempts = 0;
            var maxAttempts = 100;
            function check() {
                if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function') {
                    callback();
                } else if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(check, 50);
                } else {
                    // Fallback: show error
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Login';
                    }
                    console.error('reCAPTCHA failed to load after', maxAttempts * 50, 'ms');
                    showToast('reCAPTCHA failed to load. Please check your internet connection and refresh the page.', 'error', 8000);
                }
            }
            check();
        }
        
        // Pre-load token when page loads for faster submission
        function preloadToken() {
            waitForRecaptcha(function() {
                if (typeof grecaptcha !== 'undefined' && siteKey) {
                    grecaptcha.ready(function() {
                        try {
                            grecaptcha.execute(siteKey, { action: 'login' }).then(function(token) {
                                if (tokenInput && token) {
                                    tokenInput.value = token;
                                    console.log('reCAPTCHA token preloaded successfully');
                                }
                            }).catch(function(error) {
                                console.error('reCAPTCHA preload error:', error);
                                // Don't show alert on preload failure, just log it
                            });
                        } catch (error) {
                            console.error('reCAPTCHA execute error:', error);
                        }
                    });
                }
            });
        }
        
        // Function to actually submit the form
        function doSubmit() {
            console.log('Submitting form with token:', tokenInput ? (tokenInput.value ? 'yes' : 'no') : 'no input');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Logging in...';
            }
            
            // Verify token is set
            if (!tokenInput || !tokenInput.value) {
                console.error('No reCAPTCHA token found!');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Login';
                }
                showToast('reCAPTCHA token missing. Please try again.', 'error');
                return;
            }
            
            // Remove the event listener completely to prevent re-interception
            form.removeEventListener('submit', submitHandler);
            
            // Submit using native form submission
            // Use a small delay to ensure listener is fully removed
            setTimeout(function() {
                console.log('Submitting form now...');
                try {
                    // Try requestSubmit first (modern browsers, triggers validation)
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        // Fallback to submit() - bypasses validation but submits
                        form.submit();
                    }
                } catch (err) {
                    console.error('Form submission error:', err);
                    // Last resort: submit via fetch
                    var formData = new FormData(form);
                    fetch(form.action, {
                        method: form.method,
                        body: formData
                    }).then(function(response) {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            window.location.reload();
                        }
                    }).catch(function(error) {
                        console.error('Fetch submission error:', error);
                        showToast('Form submission failed. Please try again.', 'error');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Login';
                        }
                    });
                }
            }, 100);
        }
        
        var submitHandler = function(e) {
            console.log('Form submit intercepted, checking for token...');
            // Always prevent default first
            e.preventDefault();
            e.stopPropagation();
            
                // If token already exists, submit immediately
                if (tokenInput && tokenInput.value) {
                    console.log('Token found, submitting immediately');
                    doSubmit();
                    return;
                }
                
                console.log('No token found, generating new one...');
            
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
                if (typeof grecaptcha === 'undefined' || typeof grecaptcha.ready !== 'function') {
                    tokenGenerating = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Login';
                    }
                    showToast('reCAPTCHA failed to load. Please refresh the page.', 'error', 8000);
                    return;
                }
                
                try {
                    grecaptcha.ready(function() {
                        console.log('Getting reCAPTCHA token with site key:', siteKey);
                        try {
                            grecaptcha.execute(siteKey, { action: 'login' }).then(function(token) {
                                console.log('reCAPTCHA token received:', token ? 'yes' : 'no');
                                if (tokenInput && token) {
                                    tokenInput.value = token;
                                } else {
                                    throw new Error('Token is empty');
                                }
                                tokenGenerating = false;
                                // Submit the form
                                doSubmit();
                            }).catch(function(error) {
                                console.error('reCAPTCHA execute error:', error);
                                tokenGenerating = false;
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.textContent = 'Login';
                                }
                                
                                // Check if it's a site key error
                                var errorMsg = error.message || error.toString();
                                var isSiteKeyError = errorMsg.includes('Invalid site key') || 
                                                    errorMsg.includes('site key') ||
                                                    errorMsg.includes('not loaded in api.js');
                                
                                if (isSiteKeyError) {
                                    console.error('reCAPTCHA Site Key Error:', siteKey);
                                    showToast('reCAPTCHA site key is invalid or not registered for this domain. Please check your configuration.', 'error', 10000);
                                } else {
                                    showToast('reCAPTCHA verification failed. Please try again or contact support.', 'error', 8000);
                                }
                            });
                        } catch (error) {
                            console.error('reCAPTCHA execute call error:', error);
                            tokenGenerating = false;
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Login';
                            }
                            
                            var errorMsg = error.message || error.toString();
                            var isSiteKeyError = errorMsg.includes('Invalid site key') || 
                                                errorMsg.includes('site key') ||
                                                errorMsg.includes('not loaded in api.js');
                            
                            if (isSiteKeyError) {
                                console.error('reCAPTCHA Site Key Error:', siteKey);
                                showToast('reCAPTCHA site key is invalid or not registered for this domain. Please check your configuration.', 'error', 10000);
                            } else {
                                showToast('reCAPTCHA error. Please try again or contact support.', 'error', 8000);
                            }
                        }
                    });
                } catch (error) {
                    console.error('reCAPTCHA ready error:', error);
                    tokenGenerating = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Login';
                    }
                    
                    var errorMsg = error.message || error.toString();
                    var isSiteKeyError = errorMsg.includes('Invalid site key') || 
                                        errorMsg.includes('site key') ||
                                        errorMsg.includes('not loaded in api.js');
                    
                    if (isSiteKeyError) {
                        console.error('reCAPTCHA Site Key Error:', siteKey);
                        showToast('reCAPTCHA site key is invalid or not registered for this domain. Please check your configuration.', 'error', 10000);
                    } else {
                        showToast('reCAPTCHA initialization error. Please refresh the page or contact support.', 'error', 8000);
                    }
                }
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
    @if(config('services.google.client_id'))
    <script>
    (function() {
        var btn = document.getElementById('google-login-btn');
        if (!btn) return;
        var forType = btn.getAttribute('data-for') || 'resident';
        var baseUrl = btn.getAttribute('data-google-redirect-url') || '';
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var url = baseUrl;
            if (forType === 'tenant' || forType === 'resident') {
                var sel = document.getElementById('tenant_id');
                var tid = sel ? sel.value : '';
                if (!tid) { showToast('Please select your barangay first.', 'warning'); if (sel) sel.focus(); return; }
                url += (url.indexOf('?') !== -1 ? '&' : '?') + 'tenant_id=' + encodeURIComponent(tid);
            }
            window.location.href = url;
        });
    })();
    </script>
    @endif
</body>
</html>
