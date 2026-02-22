<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#111827">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:300,400,500,600,700&family=fraunces:400,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased theme-nightfall" x-data="{ workoutRunning: false }" @workout-state-changed.window="workoutRunning = $event.detail.running">
        <div class="h-dvh flex flex-col app-shell overflow-hidden">

            <!-- Page Heading -->
            @isset($header)
                <header class="shrink-0 sticky top-0 z-30 border-b border-white/10 bg-[#0b1114]/75 backdrop-blur-xl">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        <div class="app-panel rounded-2xl px-4 py-3 sm:px-6 sm:py-4">
                            {{ $header }}
                        </div>
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 min-h-0 overflow-auto">
                {{ $slot }}
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
