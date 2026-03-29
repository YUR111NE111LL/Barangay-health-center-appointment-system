<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password – {{ config('bhcas.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700" rel="stylesheet" />
</head>
<body class="min-h-screen overflow-x-hidden antialiased" style="font-family: 'DM Sans', ui-sans-serif, sans-serif;">
    <div class="min-h-screen overflow-visible bg-gradient-to-br from-teal-500 via-teal-600 to-cyan-700 p-4 flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-slate-900/10 ring-1 ring-white/20 backdrop-blur sm:p-8">
                <div class="mb-6 text-center">
                    <x-auth-brand-logo class="mb-4" />
                    <h1 class="text-2xl font-bold text-slate-800">Reset password</h1>
                    <p class="mt-1 text-sm text-slate-500">Enter your new password below.</p>
                </div>
                @if($errors->any())
                    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                        {{ $errors->first() }}
                    </div>
                @endif
                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">
                    @if(!empty($tenant_id))
                        <input type="hidden" name="tenant_id" value="{{ $tenant_id }}">
                    @endif
                    <div>
                        <label for="email_display" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input type="text" id="email_display" class="w-full rounded-xl border-slate-300 bg-slate-100 px-4 py-2.5 text-slate-600" value="{{ $email }}" readonly disabled>
                    </div>
                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">New password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" id="password" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required autofocus autocomplete="new-password">
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm focus:border-teal-500 focus:ring-teal-500" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-teal-600 px-4 py-3 font-semibold text-white shadow-lg shadow-teal-600/30 transition hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        Reset password
                    </button>
                </form>
                <p class="mt-4 text-center text-sm text-slate-600">
                    <a href="{{ route('login') }}" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">Back to login</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
