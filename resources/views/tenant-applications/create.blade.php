@php
    $logoPath = config('bhcas.logo_path');
    $logoUrl = $logoPath ? asset($logoPath) : null;
    $centralAppUrl = rtrim((string) config('bhcas.central_app_url', config('app.url')), '/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Apply for tenant') }} – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(\App\Support\Recaptcha::shouldProcess())
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.v3.site_key') }}" async defer></script>
    @endif
</head>
<body class="min-h-screen relative overflow-hidden bg-linear-to-br from-teal-800 via-cyan-700 to-indigo-700 text-white">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute right-0 top-40 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl"></div>
    </div>

    <header class="relative z-10 mx-auto max-w-3xl px-4 pt-8">
        <a href="{{ $centralAppUrl }}/" class="inline-flex items-center gap-2 text-sm font-medium text-white/90 hover:text-white">
            <span aria-hidden="true">←</span> {{ __('Back to home') }}
        </a>
    </header>

    <main class="relative z-10 mx-auto max-w-3xl px-4 pb-16 pt-6">
        <div class="rounded-2xl bg-white p-6 text-slate-900 shadow-2xl ring-1 ring-slate-200 sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">{{ __('Central app') }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ __('Apply for tenant') }}</h1>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        {{ __('Request a new barangay (tenant) on this platform. Enter your barangay name and contact details. The website address (domain) is set only by a Super Admin when they approve your request.') }}
                    </p>
                </div>
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="" class="h-12 w-auto rounded-lg bg-slate-50 p-1 ring-1 ring-slate-200">
                @endif
            </div>

            @if(session('status'))
                <div class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if($plans->isEmpty())
                <div class="mt-6 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
                    {{ __('No subscription plans are available yet. Please contact the platform administrator.') }}
                </div>
            @else
            <form
                action="{{ route('tenant-applications.store') }}"
                method="POST"
                class="mt-8 space-y-5"
                id="tenant-application-form"
                @if(\App\Support\Recaptcha::shouldProcess()) data-recaptcha-site-key="{{ config('services.recaptcha.v3.site_key') }}" @endif
            >
                @csrf
                @if(\App\Support\Recaptcha::shouldProcess())
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                @endif

                @if($errors->any())
                    <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                        <ul class="list-inside list-disc">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-2">
                    <h2 class="text-sm font-semibold text-slate-800">{{ __('Plan pricing') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Monthly subscription (before approval). Final terms are confirmed when a Super Admin approves your barangay.') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($plans as $plan)
                            <div class="rounded-xl border border-slate-200 bg-slate-50/90 p-4 shadow-sm ring-1 ring-slate-100">
                                <div class="text-sm font-semibold text-slate-900">{{ $plan->name }}</div>
                                <div class="mt-2 flex flex-wrap items-baseline gap-1">
                                    <span class="text-2xl font-bold text-teal-700">{{ $plan->formattedPrice() }}</span>
                                    @if($plan->price !== null)
                                        <span class="text-xs font-medium text-slate-500">{{ __('/ month') }}</span>
                                    @endif
                                </div>
                                <p class="mt-3 text-xs leading-relaxed text-slate-600">{{ $plan->pricingSummaryLine() }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="plan_id" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Choose your plan') }} <span class="text-rose-500">*</span></label>
                    <select name="plan_id" id="plan_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
                        @foreach($plans as $plan)
                            <option
                                value="{{ $plan->id }}"
                                data-price="{{ e($plan->formattedPrice()) }}"
                                data-summary="{{ e($plan->pricingSummaryLine()) }}"
                                {{ (string) old('plan_id') === (string) $plan->id ? 'selected' : '' }}
                            >{{ $plan->name }}</option>
                        @endforeach
                    </select>
                    <div id="plan-selection-summary" class="mt-2 whitespace-pre-line rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-900 ring-1 ring-teal-100/80" role="status" aria-live="polite"></div>
                    @error('plan_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
                    @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="barangay" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Barangay name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="barangay" id="barangay" value="{{ old('barangay') }}" placeholder="{{ __('e.g. Barangay Sumpong') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" required>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Official or common name of the barangay. Do not enter a website address here.') }}</p>
                    @error('barangay')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="address" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Address') }}</label>
                    <input type="text" name="address" id="address" value="{{ old('address') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                    @error('address')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="contact_number" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Contact number') }}</label>
                        <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                        @error('contact_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Email') }} <span class="text-rose-600">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" placeholder="{{ __('Used to notify you when your application is approved or rejected') }}">
                        @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if(\App\Support\Recaptcha::shouldProcess())
                    <p class="text-center text-xs text-slate-400">{{ __('Protected by reCAPTCHA') }}</p>
                @endif

                <div class="flex flex-wrap gap-3">
                    <button
                        type="submit"
                        id="tenant-application-submit"
                        class="rounded-xl bg-teal-600 px-5 py-2.5 font-medium text-white shadow-sm hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                        data-label-default="{{ e(__('Submit application')) }}"
                        data-label-submitting="{{ e(__('Submitting…')) }}"
                    >
                        {{ __('Submit application') }}
                    </button>
                    <a href="{{ $centralAppUrl }}/" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-medium text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                </div>
            </form>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var sel = document.getElementById('plan_id');
                var box = document.getElementById('plan-selection-summary');
                if (! sel || ! box) {
                    return;
                }
                function updatePlanSummary() {
                    var opt = sel.options[sel.selectedIndex];
                    if (! opt) {
                        return;
                    }
                    var price = opt.getAttribute('data-price') || '';
                    var summary = opt.getAttribute('data-summary') || '';
                    var name = opt.textContent.trim();
                    var text = name + ' — ' + price;
                    if (summary) {
                        text += '\n' + summary;
                    }
                    box.textContent = text;
                }
                sel.addEventListener('change', updatePlanSummary);
                updatePlanSummary();
            });
            </script>
            @endif
        </div>
    </main>

    @if(\App\Support\Recaptcha::shouldProcess())
    <script>
    (function(){
        var form = document.getElementById('tenant-application-form');
        var tokenInput = form && document.getElementById('recaptcha_token');
        var submitBtn = form && document.getElementById('tenant-application-submit');
        var siteKey = form ? form.getAttribute('data-recaptcha-site-key') : '';
        var isSubmitting = false;
        var labelSubmitting = submitBtn ? submitBtn.getAttribute('data-label-submitting') : '';
        var labelDefault = submitBtn ? submitBtn.getAttribute('data-label-default') : '';

        function doSubmit(){
            if (isSubmitting) { return; }
            isSubmitting = true;
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = labelSubmitting || ''; }
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
                if (! tokenInput || tokenInput.value) { return; }
                grecaptcha.ready(function(){
                    grecaptcha.execute(siteKey, { action: 'tenant_application' }).then(function(token){
                        if (tokenInput && token) { tokenInput.value = token; }
                    }).catch(function(){});
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
                    grecaptcha.execute(siteKey, { action: 'tenant_application' }).then(function(token){
                        if (tokenInput && token) tokenInput.value = token;
                        doSubmit();
                    }).catch(function(){
                        isSubmitting = false;
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = labelDefault || ''; }
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
