@php
    $pageTitle = 'Forgot password';
    $subtitle = 'Enter your email and we’ll send you a link to reset your password.';
    if (($for ?? '') === 'super-admin') {
        $pageTitle = 'Super Admin – Forgot password';
        $subtitle = 'Enter your email to receive a reset link.';
    } elseif (($for ?? '') === 'tenant') {
        $pageTitle = 'Staff & Nurse – Forgot password';
        $subtitle = 'Select your barangay and enter your email to receive a reset link.';
    } elseif (($for ?? '') === 'resident') {
        $pageTitle = 'Resident – Forgot password';
        $subtitle = 'Select your barangay and enter your email to receive a reset link.';
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
</head>
<body class="min-h-screen overflow-x-hidden antialiased" style="font-family: 'DM Sans', ui-sans-serif, sans-serif;">
    <div class="min-h-screen overflow-visible bg-gradient-to-br from-teal-500 via-teal-600 to-cyan-700 p-4 flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-slate-900/10 ring-1 ring-white/20 backdrop-blur sm:p-8">
                <div class="mb-6 text-center">
                    <h1 class="text-2xl font-bold text-slate-800">{{ $pageTitle }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
                </div>
                @if($errors->any())
                    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                        {{ $errors->first() }}
                    </div>
                @endif
                @if(session('status'))
                    <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                        {{ session('status') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('password.email') }}" id="forgot-password-form" class="space-y-4"
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug')) data-recaptcha-site-key="{{ config('services.recaptcha.v3.site_key') }}" @endif>
                    @csrf
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                    @endif
                    @if(!empty($for))
                        <input type="hidden" name="for" value="{{ $for }}">
                    @endif
                    @if(in_array($for ?? '', ['tenant', 'resident']) && $tenants->isNotEmpty())
                        <div>
                            <label for="tenant_id" class="mb-1 block text-sm font-medium text-slate-700">Select your barangay <span class="text-rose-500">*</span></label>
                            <select name="tenant_id" id="tenant_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                                <option value="">— Choose barangay —</option>
                                @foreach($tenants as $t)
                                    <option value="{{ $t->id }}" {{ (string) old('tenant_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required autofocus>
                    </div>
                    <button type="submit" id="forgot-submit" class="w-full rounded-xl bg-teal-600 px-4 py-3 font-semibold text-white shadow-lg shadow-teal-600/30 transition hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        Send password reset link
                    </button>
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
                        <p class="text-center text-xs text-slate-400">Protected by reCAPTCHA</p>
                    @endif
                </form>
                <p class="mt-4 text-center text-sm text-slate-600">
                    <a href="{{ route('login', ['for' => $for ?? 'resident']) }}" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">Back to login</a>
                </p>
            </div>
        </div>
    </div>
    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
    <script>
    (function() {
        var form = document.getElementById('forgot-password-form');
        var submitBtn = document.getElementById('forgot-submit');
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
                        submitBtn.textContent = 'Send password reset link';
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
                        grecaptcha.execute(siteKey, { action: 'forgot_password' }).then(function(token) {
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
                        submitBtn.textContent = 'Send password reset link';
                    }
                    showToast('reCAPTCHA failed to load. Please refresh the page.', 'error', 8000);
                    return;
                }
                
                grecaptcha.ready(function() {
                    grecaptcha.execute(siteKey, { action: 'forgot_password' }).then(function(token) {
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
                            submitBtn.textContent = 'Send password reset link';
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
    @include('components.professional-alerts')
</body>
</html>
