@php
    $logoPath = config('bhcas.logo_path');
    $logoUrl = $logoPath ? asset($logoPath) : null;
    $centralAppUrl = rtrim((string) config('bhcas.central_app_url', config('app.url')), '/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Apply for tenant') }} – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(\App\Support\Recaptcha::shouldLoadClient())
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.v3.site_key') }}" async defer></script>
    @endif
    <style>
        /* Smooth, premium animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        /* Custom focus ring for better accessibility */
        .focus-ring-custom:focus {
            outline: 2px solid #0d9488;
            outline-offset: 2px;
        }
        /* Smooth transitions for interactive elements */
        .transition-smooth {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="min-h-screen font-sans antialiased bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-slate-100 selection:bg-teal-500/30 selection:text-teal-200">

    <!-- Refined background decorative elements -->
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-24 -left-24 h-[40rem] w-[40rem] rounded-full bg-gradient-to-br from-teal-500/20 to-cyan-500/20 blur-3xl animate-float"></div>
        <div class="absolute bottom-0 right-0 h-[35rem] w-[35rem] rounded-full bg-gradient-to-tl from-blue-600/20 to-indigo-600/20 blur-3xl animate-float animation-delay-2000"></div>
        <div class="absolute top-1/3 left-2/3 h-[25rem] w-[25rem] rounded-full bg-purple-600/10 blur-3xl animate-float animation-delay-4000"></div>
    </div>

    <main class="relative z-10 mx-auto max-w-7xl px-4 pb-16 pt-8 sm:px-6 lg:px-8">
        <!-- Back link with refined styling -->
        <a href="{{ $centralAppUrl }}/" class="group inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition-smooth hover:text-white">
            <span class="text-lg leading-5 transition-transform group-hover:-translate-x-1" aria-hidden="true">←</span> 
            <span class="border-b border-transparent group-hover:border-white/40">{{ __('Back to home') }}</span>
        </a>

        <!-- Main card with glassmorphism and enhanced depth -->
        <section class="mt-6 overflow-hidden rounded-3xl bg-slate-900/80 p-6 shadow-2xl ring-1 ring-white/10 backdrop-blur-xl backdrop-saturate-150 sm:p-8 lg:p-10">
            <!-- Header area -->
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-teal-400">{{ __('Central application') }}</p>
                    <h1 class="mt-3 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-5xl/none">
                        {{ __('Request a new barangay site') }}
                    </h1>
                    <p class="mt-4 text-base leading-relaxed text-slate-300 sm:text-lg">
                        {{ __('Complete the form below to apply for a barangay tenant. Provide your barangay details and choose a subscription plan.') }}
                        @if((bool) config('bhcas.auto_provision_tenant_for_any_email_applications', false) || (bool) config('bhcas.auto_provision_tenant_for_gmail_applications', false))
                            {{ __('Upon submission, your barangay site and Barangay Admin account can be created automatically using the email you enter.') }}
                        @else
                            {{ __('The website address (domain) will be configured by a Super Admin once your application is approved.') }}
                        @endif
                    </p>
                </div>
                @if($logoUrl)
                    <div class="shrink-0">
                        <img src="{{ $logoUrl }}" alt="{{ config('bhcas.name') }}" class="h-16 w-auto rounded-2xl bg-white/5 p-2 ring-1 ring-white/20 backdrop-blur-sm">
                    </div>
                @endif
            </div>

            <!-- Status message (success/error) -->
            @if(session('status'))
                <div class="mt-8 rounded-xl bg-emerald-500/10 px-5 py-4 text-sm text-emerald-200 ring-1 ring-emerald-500/30 backdrop-blur-sm">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 shrink-0 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                </div>
            @endif

            <!-- Two‑column layout: tracking sidebar + main form -->
            <div class="mt-10 grid gap-8 lg:grid-cols-5">
                <!-- Sidebar: Application status tracking -->
                <aside class="lg:col-span-2 lg:sticky lg:top-8 lg:self-start">
                    <div class="rounded-2xl bg-slate-800/50 p-5 ring-1 ring-white/10 backdrop-blur-sm">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-teal-500/20 p-2">
                                <svg class="h-5 w-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h2 class="text-lg font-semibold text-white">{{ __('Check application status') }}</h2>
                        </div>
                        <p class="mt-2 text-sm text-slate-400">
                            {{ __('Enter the email address you used to apply. You’ll see the current status of all applications associated with it.') }}
                        </p>
                        <form action="{{ route('tenant-applications.create') }}" method="GET" class="mt-5 space-y-3">
                            <div>
                                <label for="track_email" class="sr-only">{{ __('Email address') }}</label>
                                <input
                                    type="email"
                                    name="track_email"
                                    id="track_email"
                                    value="{{ $trackingEmail ?? '' }}"
                                    placeholder="{{ __('you@example.com') }}"
                                    class="w-full rounded-xl border-0 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:ring-2 focus:ring-teal-500 focus:ring-inset"
                                >
                            </div>
                            <button type="submit" class="w-full rounded-xl bg-white/10 px-4 py-3 text-sm font-medium text-white transition-smooth hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                                {{ __('View status') }}
                            </button>
                        </form>

                        @if(filled($trackingEmail ?? '') && isset($trackedApplications))
                            <div class="mt-6 space-y-3" id="status-results-block" data-autohide-ms="15000">
                                @if($trackedApplications->isEmpty())
                                    <div class="rounded-xl bg-slate-900/50 p-4 text-sm text-slate-400">
                                        {{ __('No applications found for this email.') }}
                                    </div>
                                @else
                                    @foreach($trackedApplications as $application)
                                        <div class="rounded-xl bg-slate-900/50 p-4 text-sm transition-smooth hover:bg-slate-900/70">
                                            <div class="flex flex-wrap items-center justify-end gap-3">
                                                @if($application->status === \App\Models\TenantApplication::STATUS_PENDING)
                                                    <span class="inline-flex items-center rounded-full bg-amber-500/20 px-2.5 py-1 text-xs font-medium text-amber-200 ring-1 ring-amber-500/30">
                                                        {{ __('Pending') }}
                                                    </span>
                                                @elseif($application->status === \App\Models\TenantApplication::STATUS_APPROVED)
                                                    <span class="inline-flex items-center rounded-full bg-emerald-500/20 px-2.5 py-1 text-xs font-medium text-emerald-200 ring-1 ring-emerald-500/30">
                                                        {{ __('Approved') }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-rose-500/20 px-2.5 py-1 text-xs font-medium text-rose-200 ring-1 ring-rose-500/30">
                                                        {{ __('Rejected') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="mt-2 text-xs text-slate-400">
                                                {{ __('Submitted') }} {{ \App\Support\DateDisplay::format($application->created_at) }}
                                            </p>
                                            @if($application->status === \App\Models\TenantApplication::STATUS_REJECTED && filled($application->rejection_reason))
                                                <div class="mt-3 rounded-lg bg-rose-500/10 p-3 text-xs text-rose-200 ring-1 ring-rose-500/30">
                                                    <span class="font-semibold">{{ __('Reason:') }}</span> {{ $application->rejection_reason }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                    <p class="text-[11px] text-slate-500">{{ __('Status cards auto-hide after 15 seconds for privacy.') }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </aside>

                <!-- Main form column -->
                <div class="lg:col-span-3">
                    @if($plans->isEmpty())
                        <div class="rounded-xl bg-amber-500/10 p-5 text-amber-200 ring-1 ring-amber-500/30">
                            {{ __('No subscription plans are available. Please contact the platform administrator.') }}
                        </div>
                    @else
                        <form
                            action="{{ route('tenant-applications.store') }}"
                            method="POST"
                            class="space-y-8"
                            id="tenant-application-form"
                            @if(\App\Support\Recaptcha::shouldLoadClient()) data-recaptcha-site-key="{{ config('services.recaptcha.v3.site_key') }}" @endif
                        >
                            @csrf
                            @if(\App\Support\Recaptcha::shouldLoadClient())
                                <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                            @endif

                            <!-- Error summary -->
                            @if($errors->any())
                                <div class="rounded-xl bg-rose-500/10 p-4 text-sm text-rose-200 ring-1 ring-rose-500/30">
                                    <ul class="list-inside list-disc space-y-1">
                                        @foreach($errors->all() as $e)
                                            <li>{{ $e }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Plan cards with modern design -->
                            <div>
                                <div class="mb-5">
                                    <h2 class="text-xl font-semibold text-white">{{ __('Choose a subscription plan') }}</h2>
                                    <p class="mt-1 text-sm text-slate-400">
                                        {{ __('Monthly pricing shown. Final terms are confirmed upon approval.') }}
                                    </p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($plans as $plan)
                                        @php
                                            $featureLabels = $plan->applyForTenantFeatureLabels();
                                            $planTheme = match ($plan->slug) {
                                                'basic' => [
                                                    'accent' => 'from-cyan-500 to-blue-500',
                                                    'badge' => 'bg-cyan-500/20 text-cyan-200 ring-cyan-500/30',
                                                    'card' => 'hover:border-cyan-500/50',
                                                    'dot' => 'bg-cyan-400',
                                                ],
                                                'standard' => [
                                                    'accent' => 'from-violet-500 to-purple-500',
                                                    'badge' => 'bg-violet-500/20 text-violet-200 ring-violet-500/30',
                                                    'card' => 'hover:border-violet-500/50',
                                                    'dot' => 'bg-violet-400',
                                                ],
                                                'premium' => [
                                                    'accent' => 'from-amber-500 to-orange-500',
                                                    'badge' => 'bg-amber-500/20 text-amber-200 ring-amber-500/30',
                                                    'card' => 'hover:border-amber-500/50',
                                                    'dot' => 'bg-amber-400',
                                                ],
                                                default => [
                                                    'accent' => 'from-slate-500 to-slate-600',
                                                    'badge' => 'bg-slate-500/20 text-slate-200 ring-slate-500/30',
                                                    'card' => 'hover:border-slate-500/50',
                                                    'dot' => 'bg-slate-400',
                                                ],
                                            };
                                        @endphp
                                        <div class="group relative rounded-2xl border border-white/10 bg-slate-800/40 p-5 backdrop-blur-sm transition-all duration-300 {{ $planTheme['card'] }} hover:-translate-y-1 hover:shadow-xl hover:shadow-{{ explode('-', $planTheme['accent'])[1] }}-500/20">
                                            <!-- Gradient accent bar -->
                                            <div class="absolute inset-x-0 top-0 h-1 rounded-t-2xl bg-gradient-to-r {{ $planTheme['accent'] }} opacity-80"></div>
                                            
                                            <div class="mb-3">
                                                <span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide ring-1 {{ $planTheme['badge'] }}">
                                                    {{ $plan->name }}
                                                </span>
                                            </div>
                                            <div class="mt-2 flex items-baseline gap-1">
                                                <span class="text-3xl font-bold text-white">{{ $plan->formattedPrice() }}</span>
                                                @if($plan->price !== null)
                                                    <span class="text-sm text-slate-400">{{ __('/ month') }}</span>
                                                @endif
                                            </div>
                                            <p class="mt-3 text-sm text-slate-300">{{ $plan->pricingSummaryLine() }}</p>
                                            <div class="mt-5">
                                                <div class="mb-2 text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('Includes') }}</div>
                                                <ul class="space-y-2">
                                                    @forelse($featureLabels as $feature)
                                                        <li class="flex items-start gap-2 text-sm text-slate-300">
                                                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full {{ $planTheme['dot'] }}"></span>
                                                            <span>{{ $feature }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-sm text-slate-500">{{ __('No additional features') }}</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Plan selection dropdown with live summary -->
                                <div class="mt-6">
                                    <label for="plan_id" class="block text-sm font-medium text-slate-300">
                                        {{ __('Select your plan') }} <span class="text-rose-400">*</span>
                                    </label>
                                    <select 
                                        name="plan_id" 
                                        id="plan_id" 
                                        class="mt-2 block w-full rounded-xl border-0 bg-white/5 px-4 py-3 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-teal-500 sm:text-sm"
                                        required
                                    >
                                        @foreach($plans as $plan)
                                            <option
                                                value="{{ $plan->id }}"
                                                data-price="{{ e($plan->formattedPrice()) }}"
                                                data-summary="{{ e($plan->pricingSummaryLine()) }}"
                                                {{ (string) old('plan_id') === (string) $plan->id ? 'selected' : '' }}
                                                class="bg-slate-800"
                                            >{{ $plan->name }}</option>
                                        @endforeach
                                    </select>
                                    <div id="plan-selection-summary" class="mt-3 rounded-lg bg-teal-500/10 p-3 text-sm text-teal-200 ring-1 ring-teal-500/30" role="status" aria-live="polite"></div>
                                    @error('plan_id')<p class="mt-2 text-sm text-rose-400">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Contact & Barangay details with improved fields -->
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="name" class="block text-sm font-medium text-slate-300">
                                        {{ __('Your full name') }} <span class="text-rose-400">*</span>
                                    </label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                           class="mt-2 block w-full rounded-xl border-0 bg-white/5 px-4 py-3 text-white shadow-sm ring-1 ring-inset ring-white/10 placeholder:text-slate-500 focus:ring-2 focus:ring-teal-500 sm:text-sm"
                                           required>
                                    @error('name')<p class="mt-2 text-sm text-rose-400">{{ $message }}</p>@enderror
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="barangay" class="block text-sm font-medium text-slate-300">
                                        {{ __('Barangay name') }} <span class="text-rose-400">*</span>
                                    </label>
                                    <input type="text" name="barangay" id="barangay" value="{{ old('barangay') }}" 
                                           placeholder="{{ __('e.g., Barangay Sumpong') }}"
                                           class="mt-2 block w-full rounded-xl border-0 bg-white/5 px-4 py-3 text-white shadow-sm ring-1 ring-inset ring-white/10 placeholder:text-slate-500 focus:ring-2 focus:ring-teal-500 sm:text-sm"
                                           required>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Official name of the barangay (do not enter a domain).') }}</p>
                                    @error('barangay')<p class="mt-2 text-sm text-rose-400">{{ $message }}</p>@enderror
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-slate-300">{{ __('Complete address') }}</label>
                                    <input type="text" name="address" id="address" value="{{ old('address') }}" 
                                           class="mt-2 block w-full rounded-xl border-0 bg-white/5 px-4 py-3 text-white shadow-sm ring-1 ring-inset ring-white/10 placeholder:text-slate-500 focus:ring-2 focus:ring-teal-500 sm:text-sm">
                                    @error('address')<p class="mt-2 text-sm text-rose-400">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="contact_number" class="block text-sm font-medium text-slate-300">{{ __('Contact number') }}</label>
                                    <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" 
                                           class="mt-2 block w-full rounded-xl border-0 bg-white/5 px-4 py-3 text-white shadow-sm ring-1 ring-inset ring-white/10 placeholder:text-slate-500 focus:ring-2 focus:ring-teal-500 sm:text-sm">
                                    @error('contact_number')<p class="mt-2 text-sm text-rose-400">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-slate-300">
                                        {{ __('Email address') }} <span class="text-rose-400">*</span>
                                    </label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}" 
                                           required autocomplete="email"
                                           placeholder="{{ __('you@example.com') }}"
                                           class="mt-2 block w-full rounded-xl border-0 bg-white/5 px-4 py-3 text-white shadow-sm ring-1 ring-inset ring-white/10 placeholder:text-slate-500 focus:ring-2 focus:ring-teal-500 sm:text-sm">
                                    <p class="mt-1 text-xs text-slate-500">{{ __('We’ll use this to notify you about your application status.') }}</p>
                                    @error('email')<p class="mt-2 text-sm text-rose-400">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Form actions -->
                            <div class="flex flex-wrap items-center gap-4 pt-4">
                                <button
                                    type="submit"
                                    id="tenant-application-submit"
                                    class="group relative inline-flex items-center justify-center overflow-hidden rounded-xl bg-gradient-to-r from-teal-500 to-emerald-500 px-6 py-3 font-medium text-white shadow-lg shadow-teal-500/25 transition-all duration-300 hover:shadow-xl hover:shadow-teal-500/30 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-slate-900 disabled:opacity-70"
                                    data-label-default="{{ e(__('Submit application')) }}"
                                    data-label-submitting="{{ e(__('Submitting…')) }}"
                                >
                                    <span class="relative">{{ __('Submit application') }}</span>
                                </button>
                                <button
                                    type="submit"
                                    formaction="{{ route('tenant-applications.google.start') }}"
                                    formmethod="POST"
                                    class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/5 px-6 py-3 font-medium text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/10 hover:border-white/30 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-slate-900"
                                    data-label-default="{{ e(__('Apply with Google')) }}"
                                    data-label-submitting="{{ e(__('Redirecting…')) }}"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                    </svg>
                                    <span>{{ __('Apply with Google') }}</span>
                                </button>
                                <a href="{{ $centralAppUrl }}/" class="rounded-xl border border-white/20 bg-transparent px-6 py-3 font-medium text-slate-300 transition-all duration-300 hover:bg-white/5 hover:text-white">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>

                        <!-- Plan summary updater script -->
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const select = document.getElementById('plan_id');
                                const summaryBox = document.getElementById('plan-selection-summary');
                                if (!select || !summaryBox) return;

                                function updateSummary() {
                                    const option = select.options[select.selectedIndex];
                                    if (!option) return;
                                    const price = option.getAttribute('data-price') || '';
                                    const summary = option.getAttribute('data-summary') || '';
                                    const name = option.textContent.trim();
                                    let text = `${name} — ${price}`;
                                    if (summary) text += `\n${summary}`;
                                    summaryBox.textContent = text;
                                }

                                select.addEventListener('change', updateSummary);
                                updateSummary();
                            });
                        </script>
                    @endif
                </div>
            </div>
        </section>
    </main>

    <!-- reCAPTCHA v3 logic (unchanged) -->
    @if(\App\Support\Recaptcha::shouldLoadClient())
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
            var submitter = e.submitter || null;
            if (submitter && submitter.getAttribute) {
                submitBtn = submitter;
                var fa = submitter.getAttribute('formaction');
                if (fa) {
                    form.setAttribute('action', fa);
                }
                var defaultLabel = submitter.getAttribute('data-label-default');
                var submittingLabel = submitter.getAttribute('data-label-submitting');
                if (defaultLabel) { labelDefault = defaultLabel; }
                if (submittingLabel) { labelSubmitting = submittingLabel; }
            }
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
    <script>
    (function () {
        var statusResultsBlock = document.getElementById('status-results-block');
        if (! statusResultsBlock) {
            return;
        }
        var trackingEmailInput = document.getElementById('track_email');
        var autoHideMs = parseInt(statusResultsBlock.getAttribute('data-autohide-ms') || '15000', 10);
        if (Number.isNaN(autoHideMs) || autoHideMs < 1000) {
            autoHideMs = 15000;
        }
        setTimeout(function () {
            statusResultsBlock.classList.add('hidden');
            if (trackingEmailInput) {
                trackingEmailInput.value = '';
            }
            if (window.history && typeof window.history.replaceState === 'function') {
                var cleanedUrl = window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, cleanedUrl);
            }
        }, autoHideMs);
    })();
    </script>
</body>
</html>