<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pending approval – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
</head>
<body class="min-h-screen antialiased" style="font-family: 'DM Sans', ui-sans-serif, sans-serif;">
    <div class="min-h-screen bg-gradient-to-br from-amber-500 via-orange-500 to-rose-600 p-4 flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-slate-900/10 ring-1 ring-white/20 backdrop-blur sm:p-8">
                <div class="mb-6 text-center">
                    <x-auth-brand-logo class="mb-4" />
                    <h1 class="text-2xl font-bold text-slate-800">Account pending approval</h1>
                </div>
                @if(session('status'))
                    <p class="mb-6 text-center text-slate-600">{{ session('status') }}</p>
                @else
                    <p class="mb-6 text-center text-slate-600">Your account has been created and is waiting for approval by a Super Admin. You will be able to log in once it has been approved.</p>
                @endif
                <p class="text-center text-sm text-slate-500">You can close this page. We will not email you when approved; please try logging in again later.</p>
                <p class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="inline-flex rounded-xl bg-slate-700 px-4 py-2.5 font-medium text-white hover:bg-slate-800">Back to login</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
