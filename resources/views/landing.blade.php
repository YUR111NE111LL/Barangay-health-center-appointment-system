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
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('bhcas.name', 'Barangay Health Center') }} - Landing</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen relative overflow-hidden bg-linear-to-br from-teal-800 via-cyan-700 to-indigo-700 text-white">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-teal-300/10 blur-3xl"></div>
        </div>

        <header class="relative z-10 mx-auto max-w-6xl px-4 pt-8">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <span class="text-sm font-bold">{{ strtoupper(mb_substr(config('bhcas.name', 'BHC'), 0, 1)) }}</span>
                    </div>
                    <div class="leading-tight">
                        <div class="text-sm font-semibold text-white/90">{{ config('bhcas.name', 'Barangay Health Center') }}</div>
                        <div class="text-xs text-white/70">Appointment System</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="relative z-10 mx-auto max-w-6xl px-4 pb-16 pt-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Left: Central app --}}
                <section class="rounded-2xl bg-white/10 p-6 ring-1 ring-white/15 backdrop-blur-sm shadow-2xl shadow-black/10 lg:p-8">
                    <div class="flex flex-col gap-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-white/70">Central app</p>
                                <h1 class="mt-1 text-2xl font-bold">Central App Login</h1>
                                <p class="mt-2 max-w-md text-sm text-white/80 leading-relaxed">
                                    Platform administrator access. Manage tenants and plans from the central dashboard.
                                </p>
                            </div>

                            @if($logoUrl)
                                <img
                                    src="{{ $logoUrl }}"
                                    alt="{{ config('bhcas.name') }}"
                                    class="h-14 w-auto rounded-xl bg-white/90 p-2 ring-1 ring-white/20 shadow-sm"
                                >
                            @endif
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a
                                href="{{ route('login', ['for' => 'super-admin']) }}"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-teal-800 shadow-sm ring-1 ring-white/20 transition hover:bg-slate-100 hover:shadow-md sm:w-auto"
                            >
                                Log in to Central app
                            </a>
                        </div>

                    </div>
                </section>

                {{-- Right: Resident / Staff login at barangay domain --}}
                <section class="rounded-2xl bg-white p-6 text-slate-900 ring-1 ring-slate-200 shadow-2xl shadow-black/5 lg:p-8">
                    <div class="flex flex-col gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Resident / Staff</p>
                            <h2 class="mt-1 text-2xl font-bold">Choose your barangay</h2>
                            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                                Pick the barangay where you are registered, then log in as Resident or Staff/Nurse.
                            </p>
                        </div>

                        @if($tenants->isNotEmpty())
                            <div class="flex flex-col gap-3">
                                @foreach($tenants as $t)
                                    @php
                                        $domain = $t->domains->first()->domain ?? null;
                                        if (! $domain) {
                                            continue;
                                        }

                                        $residentLoginUrl = $scheme . '://' . $domain . $portSuffix . '/login?for=resident';
                                        $staffLoginUrl = $scheme . '://' . $domain . $portSuffix . '/login?for=tenant';
                                    @endphp

                                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 shadow-sm transition hover:shadow-md">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0">
                                                @php
                                                    $hostOnly = explode(':', (string) $domain)[0];
                                                    $firstLabel = explode('.', $hostOnly)[0] ?? '';
                                                    $barangayDisplay = ucwords(str_replace('-', ' ', $firstLabel));
                                                @endphp
                                                <div class="truncate text-base font-semibold text-slate-900">{{ $barangayDisplay }}</div>
                                                <div class="text-xs text-slate-600">Domain: {{ $domain }}</div>
                                            </div>

                                            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                                <a
                                                    href="{{ $residentLoginUrl }}"
                                                    class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700"
                                                >
                                                    Resident
                                                </a>
                                                <a
                                                    href="{{ $staffLoginUrl }}"
                                                    class="inline-flex items-center justify-center rounded-xl border border-teal-600/30 bg-white px-4 py-2 text-sm font-semibold text-teal-800 shadow-sm transition hover:bg-teal-50 hover:border-teal-600/40"
                                                >
                                                    Staff / Nurse
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700 ring-1 ring-rose-200">
                                No active barangays found yet. Please contact the platform administrator.
                            </div>
                        @endif

                        @if(Route::has('sign-up'))
                            <div class="mt-2 rounded-xl bg-linear-to-r from-teal-50 to-cyan-50 p-4 ring-1 ring-teal-200">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="text-sm font-semibold text-teal-900">Create an account</div>
                                        <div class="text-xs text-teal-800">Choose your barangay and role during sign-up.</div>
                                    </div>
                                    <a
                                        href="{{ route('sign-up') }}"
                                        class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800"
                                    >
                                        Sign up
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>

