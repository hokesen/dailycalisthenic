<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#111827">
        <meta name="description" content="Build a daily practice habit with guided bodyweight exercises. Fix desk posture, strengthen your back, and build core strength in 15 minutes a day. No gym required.">

        <!-- Open Graph / Social Sharing -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="Daily Calisthenics - Fix Your Desk Posture">
        <meta property="og:description" content="Build a daily practice habit with guided bodyweight exercises. Fix desk posture, strengthen your back, and build core strength in 15 minutes a day.">

        <title>Daily Calisthenics - Fix Your Desk Posture</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:300,400,500,600,700&family=fraunces:400,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased theme-nightfall app-shell">
        <!-- Navigation -->
        <nav class="border-b border-slate-800/70 backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-white">Daily Calisthenics</h1>
                    </div>
                    @if (Route::has('login'))
                        <div class="flex items-center gap-4">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-slate-300 hover:text-white transition-colors">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="text-slate-300 hover:text-white transition-colors">Log in</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-500 transition-colors">Get Started</a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
                <div class="text-center">
                    <h2 class="text-4xl md:text-6xl font-bold text-white mb-8 leading-tight">
                        Sitting creates tension.<br>
                        <span class="text-emerald-200/70">Humans need to move.</span>
                    </h2>
                    <p class="text-xl md:text-2xl text-slate-300 mb-10 max-w-2xl mx-auto leading-relaxed">
                        This app helps you practice movement every day.
                    </p>
                    @guest
                        <a href="{{ route('register') }}" class="inline-block px-8 py-4 bg-emerald-600 text-white text-lg font-semibold rounded-lg hover:bg-emerald-500 transition-colors">
                            Begin
                        </a>
                    @endguest
                </div>
            </div>
        </div>

        <!-- The Problem Section -->
        <div class="bg-slate-950/70 py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white text-center mb-4">Sound familiar?</h2>
                <p class="text-slate-400 text-center mb-12 text-lg">The daily reality of desk work</p>

                <div class="space-y-6">
                    <div class="flex items-start gap-4 bg-slate-900/40 border border-slate-800 rounded-lg p-5">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-1">Lower back pain that won't quit</h3>
                            <p class="text-gray-400">Standing up from your chair feels like you're 80. The ache follows you home and keeps you awake at night.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 bg-slate-900/40 border border-slate-800 rounded-lg p-5">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-1">Shoulders permanently hunched forward</h3>
                            <p class="text-gray-400">Your chest is tight, shoulders rounded. You catch your reflection and barely recognize your own posture.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 bg-slate-900/40 border border-slate-800 rounded-lg p-5">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-1">Neck strain and tension headaches</h3>
                            <p class="text-gray-400">Hours staring at monitors leave your neck stiff. The tension creeps up into headaches that kill your focus.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 bg-slate-900/40 border border-slate-800 rounded-lg p-5">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-1">Zero energy after work</h3>
                            <p class="text-gray-400">Sitting all day leaves you exhausted. No motivation to exercise. The couch wins every time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- The Real Cost Section -->
        <div class="py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-white mb-6">Neglect compounds</h2>
                <p class="text-xl text-gray-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                    When movement disappears, muscles weaken. Posture slowly adapts. What starts as discomfort often becomes persistent pain.
                </p>
                <div class="grid md:grid-cols-3 gap-6 text-center">
                    <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-6">
                        <div class="text-4xl font-bold text-red-400 mb-2">60.3%</div>
                        <p class="text-gray-400 text-sm">of desk workers report neck pain</p>
                    </div>
                    <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-6">
                        <div class="text-4xl font-bold text-red-400 mb-2">59.5%</div>
                        <p class="text-gray-400 text-sm">report lower back pain</p>
                    </div>
                    <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-6">
                        <div class="text-4xl font-bold text-red-400 mb-2">49.6%</div>
                        <p class="text-gray-400 text-sm">report shoulder pain</p>
                    </div>
                </div>
                <p class="text-center mt-6 text-xs text-gray-600">
                    <a href="https://pmc.ncbi.nlm.nih.gov/articles/PMC9800234" target="_blank" rel="noopener" class="hover:text-gray-500 transition-colors">Source: NIH - Prevalence of musculoskeletal pain among computer users</a>
                </p>
            </div>
        </div>

        <!-- The Turnaround -->
        <div class="bg-gradient-to-b from-slate-950 to-slate-900/80 py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-indigo-400 font-medium mb-4">There's a better way</p>
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                    A daily habit that<br>restores balance
                </h2>
                <p class="text-xl text-gray-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                    Targeted bodyweight exercises to strengthen your back, open your chest, and build core strength. No gym. No equipment. Just 15 minutes.
                </p>
            </div>
        </div>

        <!-- How It Helps -->
        <div class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white text-center mb-4">Built for busy people</h2>
                <p class="text-gray-400 text-center mb-12 text-lg">Fits into your schedule, not the other way around</p>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-slate-900 border border-slate-800 rounded-lg p-6">
                        <div class="w-12 h-12 bg-emerald-600 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Guided Timer</h3>
                        <p class="text-gray-400">
                            Just hit start. The app guides you through each exercise and rest. No thinking required.
                        </p>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-lg p-6">
                        <div class="w-12 h-12 bg-emerald-500 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Progressive Difficulty</h3>
                        <p class="text-gray-400">
                            Start where you are. Swap to easier or harder variations as your strength builds.
                        </p>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-lg p-6">
                        <div class="w-12 h-12 bg-amber-500 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Streaks</h3>
                        <p class="text-gray-400">
                            Build the habit. Watch your streak grow. Consistency beats intensity.
                        </p>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-lg p-6">
                        <div class="w-12 h-12 bg-emerald-600 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Custom Templates</h3>
                        <p class="text-gray-400">
                            Create your own routines or use ours. Focus on back, core, or full body.
                        </p>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-lg p-6">
                        <div class="w-12 h-12 bg-emerald-600 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Zero Equipment</h3>
                        <p class="text-gray-400">
                            Train anywhere. Home office, hotel room, or between meetings. Just your body.
                        </p>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 rounded-lg p-6">
                        <div class="w-12 h-12 bg-amber-500 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Automatic Progress</h3>
                        <p class="text-gray-400">
                            Every session saved instantly. Pick up where you left off, even mid-practice.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Simple Steps -->
        <div class="bg-slate-950/70 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white text-center mb-12">Get started in under a minute</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-white">1</span>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Pick a Template</h3>
                        <p class="text-gray-400">Choose a back-focused routine or start with full body basics.</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-white">2</span>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Press Start</h3>
                        <p class="text-gray-400">Follow the timer through each exercise. 15 minutes and you're done.</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-white">3</span>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Build the Habit</h3>
                        <p class="text-gray-400">Track your streak and watch your consistency grow day by day.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-gradient-to-r from-emerald-600 to-amber-500 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    Your back can't wait another day
                </h2>
                <p class="text-xl text-indigo-100 mb-8 max-w-2xl mx-auto">
                    Start building better habits today. 15 minutes a day. Free to use.
                </p>
                @guest
                    <a href="{{ route('register') }}" class="inline-block px-8 py-4 bg-white text-emerald-700 text-lg font-semibold rounded-lg hover:bg-amber-50 transition-colors">
                        Begin Your First Session
                    </a>
                @else
                    <a href="{{ url('/dashboard') }}" class="inline-block px-8 py-4 bg-white text-emerald-700 text-lg font-semibold rounded-lg hover:bg-amber-50 transition-colors">
                        Go to Dashboard
                    </a>
                @endguest
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-slate-950/80 border-t border-slate-800 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-center text-gray-400">
                    &copy; {{ date('Y') }} Daily Calisthenics. Built by a software engineer who got tired of back pain.
                </p>
            </div>
        </footer>
    </body>
</html>
