<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('bhcas.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] min-h-screen p-6 lg:p-8">
    <header class="w-full max-w-4xl mx-auto mb-6">
        <nav class="flex items-center justify-end gap-4 text-sm">
            <a href="{{ url('/') }}" class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#1915014a] dark:hover:border-[#62605b] rounded-sm">Home</a>
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="inline-block px-5 py-1.5 rounded-sm">Log in</a>
                    @if (Route::has('sign-up'))
                        <a href="{{ route('sign-up') }}" class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm">Sign up</a>
                    @endif
                @endauth
            @endif
        </nav>
    </header>
    <main class="w-full max-w-4xl mx-auto">
        @yield('content')
    </main>
</body>
</html>
