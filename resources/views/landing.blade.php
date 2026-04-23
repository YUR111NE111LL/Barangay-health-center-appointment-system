@php
    use App\Models\Tenant;

    $logoPath = config('bhcas.logo_path');
    $logoUrl = $logoPath ? asset($logoPath) : null;

    $tenants = Tenant::with('domains')
        ->where('is_active', true)
        ->whereHas('domains', static fn ($q) => $q->whereNotNull('domain')->where('domain', '!=', ''))
        ->orderBy('name')
        ->get();

    $scheme = request()->getScheme();
    $port = request()->getPort();
    $portSuffix = ($port && ! in_array((int) $port, [80, 443], true)) ? ':' . $port : '';

    $supportPhone = (string) config('bhcas.support.phone', '+63 900 000 0000');
    $supportPhoneTel = preg_replace('/\s+/', '', $supportPhone) ?: $supportPhone;
    $supportEmail = (string) config('bhcas.support.email', 'support@bhcas.local');
    $supportHours = (string) config('bhcas.support.hours', 'Mon–Fri, 9:00 AM - 5:00 PM');

    // Fetch plans for pricing display
    $plans = \App\Models\Plan::orderBy('price')->get();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('bhcas.name', 'Barangay Health Center') }} - Landing</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Smooth floating animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 8s ease-in-out infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        /* Gradient text effect */
        .text-gradient {
            background: linear-gradient(135deg, #ffffff 0%, #a5f3fc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body class="min-h-screen relative overflow-hidden bg-gradient-to-br from-slate-900 via-teal-900 to-slate-900 text-white font-sans antialiased">
    
    <!-- Animated background orbs -->
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-40 -left-40 h-[40rem] w-[40rem] rounded-full bg-gradient-to-br from-teal-500/30 to-cyan-500/30 blur-3xl animate-float"></div>
        <div class="absolute bottom-0 right-0 h-[35rem] w-[35rem] rounded-full bg-gradient-to-tl from-blue-600/20 to-indigo-600/20 blur-3xl animate-float animation-delay-2000"></div>
        <div class="absolute top-1/3 left-2/3 h-[25rem] w-[25rem] rounded-full bg-purple-600/10 blur-3xl animate-float animation-delay-4000"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20 backdrop-blur-sm">
                    <span class="text-lg font-bold text-gradient">{{ strtoupper(mb_substr(config('bhcas.name', 'BHC'), 0, 1)) }}</span>
                </div>
                <div>
                    <div class="text-xl font-bold tracking-tight text-white">{{ config('bhcas.name', 'Barangay Health Center') }}</div>
                    <div class="text-sm text-teal-200/80">Appointment System</div>
                </div>
            </div>
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ config('bhcas.name') }}" class="h-12 w-auto rounded-xl bg-white/90 p-1.5 ring-1 ring-white/20 shadow-lg">
            @endif
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 mx-auto max-w-7xl px-4 pb-16 pt-8 sm:px-6 lg:px-8">
        
        <!-- Hero Section with Two Cards -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            
            <!-- Left Card: Central App -->
            <section class="group rounded-3xl bg-white/5 p-8 ring-1 ring-white/10 backdrop-blur-xl backdrop-saturate-150 shadow-2xl shadow-black/20 transition-all duration-500 hover:bg-white/10">
                <div class="flex flex-col h-full">
                    <div class="flex-1">
                        <div class="inline-flex items-center gap-2 rounded-full bg-teal-500/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-teal-200 ring-1 ring-teal-500/30">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-500"></span>
                            </span>
                            Central Administration
                        </div>
                        <h1 class="mt-6 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-5xl/none">
                            Manage the <span class="text-gradient">Platform</span>
                        </h1>
                        <p class="mt-4 text-lg leading-relaxed text-slate-300">
                            Super admin access to oversee all barangay tenants, subscription plans, and platform settings.
                        </p>
                    </div>
                    
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('tenant-applications.create') }}"
                           class="inline-flex items-center justify-center rounded-xl border border-white/30 bg-white/5 px-6 py-3.5 font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/10 hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/50">
                            Apply for New Tenant
                        </a>
                    </div>
                    
                    <div class="mt-8 border-t border-white/10 pt-6">
                        <p class="text-sm text-slate-400">
                            Empowering barangay health centers with a modern appointment system that improves patient flow, reduces wait times, and ensures every resident gets timely access to essential health services.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Right Card: Resident/Staff Login -->
            <section class="rounded-3xl bg-white p-8 text-slate-900 shadow-2xl shadow-black/10 ring-1 ring-slate-200">
                <div class="flex flex-col h-full">
                    <div class="flex-1">
                        <div class="inline-flex items-center gap-2 rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-teal-800 ring-1 ring-teal-200">
                            <span class="relative flex h-2 w-2">
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-600"></span>
                            </span>
                            Barangay Portal
                        </div>
                        <h2 class="mt-6 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-5xl/none">
                            Find Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-cyan-600">Barangay</span>
                        </h2>
                        <p class="mt-4 text-lg leading-relaxed text-slate-600">
                            Select your registered barangay to view available portal access details.
                        </p>
                    </div>

                    <div class="mt-8 space-y-4">
                        @if($tenants->isNotEmpty())
                            <div class="max-h-80 space-y-3 overflow-y-auto pr-2 custom-scrollbar">
                                @foreach($tenants as $t)
                                    @php
                                        $domain = $t->domains->first()->domain ?? null;
                                        if (! $domain) continue;
                                        $residentLoginUrl = $scheme . '://' . $domain . $portSuffix . '/login?for=resident';
                                        $hostOnly = explode(':', (string) $domain)[0];
                                        $firstLabel = explode('.', $hostOnly)[0] ?? '';
                                        $barangayDisplay = ucwords(str_replace('-', ' ', $firstLabel));
                                    @endphp
                                    <a
                                        href="{{ $residentLoginUrl }}"
                                        class="block rounded-xl bg-slate-50 p-5 ring-1 ring-slate-200 transition-all duration-300 hover:shadow-lg hover:ring-slate-300 hover:bg-slate-100/70 focus:outline-none focus:ring-2 focus:ring-teal-500"
                                        title="Open {{ $barangayDisplay }}"
                                    >
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="text-lg font-semibold text-slate-900">{{ $barangayDisplay }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $domain }}</div>
                                            </div>
                                            <div class="text-xs text-slate-500 sm:text-right">
                                                Click to open barangay portal.
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl bg-amber-50 p-5 text-amber-800 ring-1 ring-amber-200">
                                <p class="font-medium">No active barangays found.</p>
                                <p class="mt-1 text-sm">Please contact the platform administrator to set up your barangay.</p>
                            </div>
                        @endif

                        <div class="rounded-xl border border-dashed border-teal-300 bg-teal-50/50 p-5 text-center">
                            <p class="text-sm font-medium text-teal-800">Want your own barangay on the platform?</p>
                            <a href="{{ route('tenant-applications.create') }}"
                               class="mt-3 inline-flex w-full items-center justify-center rounded-lg border border-teal-600/30 bg-white px-5 py-2.5 text-sm font-semibold text-teal-800 shadow-sm transition hover:bg-teal-100 sm:w-auto">
                                Apply for Tenant
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Pricing Section -->
        @if($plans->isNotEmpty())
        <section class="mt-20">
            <div class="text-center">
                <span class="inline-block rounded-full bg-teal-500/20 px-4 py-1.5 text-sm font-semibold uppercase tracking-wider text-teal-200 ring-1 ring-teal-500/30">Pricing Plans</span>
                <h2 class="mt-6 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                    Choose the <span class="text-gradient">Perfect Plan</span>
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-300">
                    Affordable monthly subscriptions for barangay health centers. All plans include core appointment features.
                </p>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-3">
                @foreach($plans as $plan)
                    @php
                        $featureLabels = method_exists($plan, 'applyForTenantFeatureLabels') ? $plan->applyForTenantFeatureLabels() : [];
                        $theme = match($plan->slug ?? '') {
                            'basic' => ['accent' => 'from-cyan-500 to-blue-500', 'badge' => 'bg-cyan-500/20 text-cyan-200 ring-cyan-500/30', 'card' => 'hover:border-cyan-500/50', 'button' => 'from-cyan-500 to-blue-500'],
                            'standard' => ['accent' => 'from-violet-500 to-purple-500', 'badge' => 'bg-violet-500/20 text-violet-200 ring-violet-500/30', 'card' => 'hover:border-violet-500/50', 'button' => 'from-violet-500 to-purple-500'],
                            'premium' => ['accent' => 'from-amber-500 to-orange-500', 'badge' => 'bg-amber-500/20 text-amber-200 ring-amber-500/30', 'card' => 'hover:border-amber-500/50', 'button' => 'from-amber-500 to-orange-500'],
                            default => ['accent' => 'from-slate-500 to-slate-600', 'badge' => 'bg-slate-500/20 text-slate-200 ring-slate-500/30', 'card' => 'hover:border-slate-500/50', 'button' => 'from-slate-500 to-slate-600'],
                        };
                    @endphp
                    <div class="group relative rounded-2xl border {{ $plan->slug === 'standard' ? 'border-teal-400/50' : 'border-white/10' }} bg-white/5 p-6 backdrop-blur-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl hover:shadow-{{ explode('-', $theme['accent'])[1] }}-500/20 {{ $theme['card'] }}">
                        @if($plan->slug === 'standard')
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 px-4 py-1 text-xs font-bold uppercase tracking-wider text-white shadow-lg">
                                Most Popular
                            </div>
                        @endif
                        
                        <div class="absolute inset-x-0 top-0 h-1.5 rounded-t-2xl bg-gradient-to-r {{ $theme['accent'] }} opacity-80"></div>
                        
                        <div class="mb-5">
                            <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide ring-1 {{ $theme['badge'] }}">
                                {{ $plan->name }}
                            </span>
                        </div>
                        
                        <div class="mt-3 flex items-baseline gap-1">
                            <span class="text-4xl font-bold text-white">{{ $plan->formattedPrice() }}</span>
                            @if($plan->price !== null)
                                <span class="text-base text-slate-400">/ month</span>
                            @endif
                        </div>
                        
                        <p class="mt-3 text-sm text-slate-300">{{ $plan->pricingSummaryLine() }}</p>
                        
                        <ul class="mt-6 space-y-3">
                            @forelse($featureLabels as $feature)
                                <li class="flex items-start gap-3 text-sm text-slate-300">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-teal-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $feature }}</span>
                                </li>
                            @empty
                                <li class="text-sm text-slate-500">Core features included</li>
                            @endforelse
                        </ul>
                        
                        <div class="mt-8">
                            <a href="{{ route('tenant-applications.create') }}?plan_id={{ $plan->id }}"
                               class="block w-full rounded-xl bg-gradient-to-r {{ $theme['button'] }} px-4 py-3 text-center text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                                Get Started
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-sm text-slate-400">
                    All plans include a 14-day free trial. No credit card required.
                    <a href="{{ route('tenant-applications.create') }}" class="ml-1 font-medium text-teal-300 hover:text-teal-200 underline decoration-teal-500/30 underline-offset-4">
                        Apply now
                    </a>
                </p>
            </div>
        </section>
        @endif

        <!-- Support Footer -->
        <footer class="mt-20 border-t border-white/10 pt-8 text-center">
            <div class="flex flex-col items-center justify-between gap-4 text-sm text-slate-400 sm:flex-row">
                <p>&copy; {{ date('Y') }} {{ config('bhcas.name', 'Barangay Health Center') }}. All rights reserved.</p>
                <div class="flex gap-6">
                    <span>📞 <a href="tel:{{ $supportPhoneTel }}" class="hover:text-white transition">{{ $supportPhone }}</a></span>
                    <span>✉️ <a href="mailto:{{ $supportEmail }}" class="hover:text-white transition">{{ $supportEmail }}</a></span>
                    <span>🕒 {{ $supportHours }}</span>
                </div>
            </div>
        </footer>
    </main>

    <style>
        /* Custom scrollbar for tenant list */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
            }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>
</body>
</html>