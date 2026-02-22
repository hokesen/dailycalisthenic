<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:300,400,500,600,700&family=fraunces:400,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased theme-nightfall">
        <div class="min-h-screen app-shell flex items-center justify-center px-4 py-10 sm:px-6">
            <div class="app-auth-shell app-reveal">
                <a href="/" class="app-auth-brand">
                    <x-application-logo class="text-base sm:text-lg" />
                </a>

                <div class="app-auth-card px-6 py-7 sm:px-8 sm:py-9">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
