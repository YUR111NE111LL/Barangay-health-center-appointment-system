<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign up – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.v3.site_key') }}" async defer></script>
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
</head>
<body class="min-h-screen antialiased" style="font-family: 'DM Sans', ui-sans-serif, sans-serif;">
    <div class="min-h-screen bg-gradient-to-br from-cyan-500 via-teal-600 to-emerald-700 p-4 flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-slate-900/10 ring-1 ring-white/20 backdrop-blur sm:p-8">
                <h1 class="mb-6 text-center text-2xl font-bold text-slate-800">Sign up</h1>
                @if($errors->any())
                    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                        <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('register') }}" id="register-form" class="space-y-4"
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug')) data-recaptcha-site-key="{{ config('services.recaptcha.v3.site_key') }}" @endif>
                    @csrf
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                    @endif
                    <div>
                        <label for="role" class="mb-1 block text-sm font-medium text-slate-700">I am signing up as <span class="text-rose-500">*</span></label>
                        <select name="role" id="role" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                            <option value="Resident" {{ old('role', 'Resident') === 'Resident' ? 'selected' : '' }}>Resident (Patient)</option>
                            <option value="Staff" {{ old('role') === 'Staff' ? 'selected' : '' }}>Staff</option>
                            <option value="Nurse" {{ old('role') === 'Nurse' ? 'selected' : '' }}>Nurse / Midwife</option>
                            <option value="Health Center Admin" {{ old('role') === 'Health Center Admin' ? 'selected' : '' }}>Barangay / Health Center Admin</option>
                        </select>
                    </div>
                    <div id="tenant_id-wrap">
                        <label for="tenant_id" class="mb-1 block text-sm font-medium text-slate-700">Health Center / Barangay <span class="text-rose-500">*</span></label>
                        <select name="tenant_id" id="tenant_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Select...</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}" {{ old('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
                    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
                        <p class="text-center text-xs text-slate-400">Protected by reCAPTCHA</p>
                    @endif
                    @if(config('services.google.client_id'))
                        <div id="google-signup-wrap" class="relative my-4">
                            <span class="relative flex justify-center text-xs text-slate-400"><span class="bg-white px-2">or</span></span>
                        </div>
                        <a href="#" id="google-signup-btn" data-google-redirect-url="{{ route('auth.google.redirect', ['for' => 'resident', 'intent' => 'signup']) }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                            Sign up with Google
                        </a>
                    @endif
                </form>
                <p class="mt-4 text-center text-sm text-slate-600"><a href="{{ route('login') }}" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">Already have an account? Log in</a></p>
            </div>
        </div>
    </div>
    @if(config('services.recaptcha.v3.site_key') && !config('app.debug'))
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
