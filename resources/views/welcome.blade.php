<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Daily Calisthenic - Track Your Bodyweight Training</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-gray-900">
        <!-- Navigation -->
        <nav class="border-b border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-white">Daily Calisthenic</h1>
                    </div>
                    @if (Route::has('login'))
                        <div class="flex items-center gap-4">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-gray-300 hover:text-white transition-colors">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-300 hover:text-white transition-colors">Log in</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Get Started</a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center">
                    <h2 class="text-5xl md:text-6xl font-bold text-white mb-6">
                        Your Bodyweight Training,
                        <span class="text-indigo-400">Tracked & Timed</span>
                    </h2>
                    <p class="text-xl text-gray-400 mb-8 max-w-2xl mx-auto">
                        Progressive calisthenics training with live workout timers, exercise tracking, and streak monitoring. No equipment needed.
                    </p>
                    @guest
                        <a href="{{ route('register') }}" class="inline-block px-8 py-4 bg-indigo-600 text-white text-lg font-semibold rounded-lg hover:bg-indigo-700 transition-colors">
                            Start Training Free
                        </a>
                    @endguest
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Live Workout Timer -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-indigo-500 transition-colors">
                    <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Live Workout Timer</h3>
                    <p class="text-gray-400">
                        Circular progress timer guides you through each exercise and rest period. Pause, skip, or mark exercises complete on the fly.
                    </p>
                </div>

                <!-- Feature 2: Track Your Streak -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-orange-500 transition-colors">
                    <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mb-4">
                        <span class="text-2xl">🔥</span>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Track Your Streak</h3>
                    <p class="text-gray-400">
                        Build consistency with daily streak tracking. See your past week at a glance and stay motivated to keep the fire burning.
                    </p>
                </div>

                <!-- Feature 3: Custom Templates -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-green-500 transition-colors">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Custom Templates</h3>
                    <p class="text-gray-400">
                        Create your own workout templates or use defaults. Set custom sets, reps, duration, and rest periods for each exercise.
                    </p>
                </div>

                <!-- Feature 4: Exercise Progression -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-purple-500 transition-colors">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Exercise Progression</h3>
                    <p class="text-gray-400">
                        Swap exercises for easier or harder variations as you progress. Build strength systematically with guided progressions.
                    </p>
                </div>

                <!-- Feature 5: Real-Time Tracking -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-blue-500 transition-colors">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Real-Time Tracking</h3>
                    <p class="text-gray-400">
                        Every exercise completion is saved instantly. Your progress is preserved even if you close the browser mid-workout.
                    </p>
                </div>

                <!-- Feature 6: No Equipment Needed -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hover:border-yellow-500 transition-colors">
                    <div class="w-12 h-12 bg-yellow-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Train Anywhere</h3>
                    <p class="text-gray-400">
                        Bodyweight-focused exercises mean no gym or equipment required. Train at home, in the park, or on the road.
                    </p>
                </div>
            </div>
        </div>

        <!-- Exercise Progressions -->
        <div class="bg-gray-950 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-white mb-4">Progressive Training Paths</h2>
                    <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                        Start at your level and progress systematically. Each exercise has easier and harder variations to match your strength.
                    </p>
                </div>

                <!-- Push-Up Progression -->
                <div class="mb-12">
                    <h3 class="text-xl font-semibold text-white mb-6 text-center">Push-Up Progression</h3>
                    <div class="flex flex-col md:flex-row items-center justify-center gap-3 overflow-x-auto pb-4">
                        <div class="flex-shrink-0 bg-green-900/20 border border-green-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-green-400 font-semibold mb-1">BEGINNER</div>
                            <div class="text-white font-medium">Wall Push-Up</div>
                        </div>
                        <svg class="w-6 h-6 text-gray-600 flex-shrink-0 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        <div class="flex-shrink-0 bg-blue-900/20 border border-blue-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-blue-400 font-semibold mb-1">INTERMEDIATE</div>
                            <div class="text-white font-medium">Push Ups</div>
                        </div>
                        <svg class="w-6 h-6 text-gray-600 flex-shrink-0 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        <div class="flex-shrink-0 bg-purple-900/20 border border-purple-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-purple-400 font-semibold mb-1">ADVANCED</div>
                            <div class="text-white font-medium">Pike Push-Ups</div>
                        </div>
                        <svg class="w-6 h-6 text-gray-600 flex-shrink-0 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        <div class="flex-shrink-0 bg-red-900/20 border border-red-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-red-400 font-semibold mb-1">ELITE</div>
                            <div class="text-white font-medium">Handstand Push-Up</div>
                        </div>
                    </div>
                </div>

                <!-- Squat Progression -->
                <div class="mb-12">
                    <h3 class="text-xl font-semibold text-white mb-6 text-center">Squat Progression</h3>
                    <div class="flex flex-col md:flex-row items-center justify-center gap-3 overflow-x-auto pb-4">
                        <div class="flex-shrink-0 bg-blue-900/20 border border-blue-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-blue-400 font-semibold mb-1">INTERMEDIATE</div>
                            <div class="text-white font-medium">Squat</div>
                        </div>
                        <svg class="w-6 h-6 text-gray-600 flex-shrink-0 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        <div class="flex-shrink-0 bg-purple-900/20 border border-purple-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-purple-400 font-semibold mb-1">ADVANCED</div>
                            <div class="text-white font-medium">Pistol Squat</div>
                        </div>
                        <svg class="w-6 h-6 text-gray-600 flex-shrink-0 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        <div class="flex-shrink-0 bg-red-900/20 border border-red-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-red-400 font-semibold mb-1">ELITE</div>
                            <div class="text-white font-medium">Dragon Squat</div>
                        </div>
                    </div>
                </div>

                <!-- Plank Progression -->
                <div>
                    <h3 class="text-xl font-semibold text-white mb-6 text-center">Plank Progression</h3>
                    <div class="flex flex-col md:flex-row items-center justify-center gap-3 overflow-x-auto pb-4">
                        <div class="flex-shrink-0 bg-green-900/20 border border-green-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-green-400 font-semibold mb-1">BEGINNER</div>
                            <div class="text-white font-medium">Kneeling Plank</div>
                        </div>
                        <svg class="w-6 h-6 text-gray-600 flex-shrink-0 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        <div class="flex-shrink-0 bg-blue-900/20 border border-blue-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-blue-400 font-semibold mb-1">INTERMEDIATE</div>
                            <div class="text-white font-medium">Plank</div>
                        </div>
                        <svg class="w-6 h-6 text-gray-600 flex-shrink-0 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        <div class="flex-shrink-0 bg-purple-900/20 border border-purple-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-purple-400 font-semibold mb-1">ADVANCED</div>
                            <div class="text-white font-medium">Three-Point Plank</div>
                        </div>
                        <svg class="w-6 h-6 text-gray-600 flex-shrink-0 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        <div class="flex-shrink-0 bg-red-900/20 border border-red-700 rounded-lg px-4 py-3 min-w-[160px] text-center">
                            <div class="text-xs text-red-400 font-semibold mb-1">ELITE</div>
                            <div class="text-white font-medium">Two-Point Plank</div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <p class="text-gray-400 text-lg">
                        <span class="text-indigo-400 font-semibold">20+ progression paths</span> help you build strength systematically from beginner to elite
                    </p>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h2 class="text-3xl font-bold text-white text-center mb-12">How It Works</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-white">1</span>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Choose Your Template</h3>
                    <p class="text-gray-400">Select a default workout or create your own custom template with your favorite exercises.</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-white">2</span>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Start Your Workout</h3>
                    <p class="text-gray-400">Hit "Go" and follow along with the live timer. Complete exercises or mark them done as you progress.</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-white">3</span>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Track Your Progress</h3>
                    <p class="text-gray-400">View your activity calendar, build streaks, and watch your strength grow over time.</p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    Ready to Start Your Journey?
                </h2>
                <p class="text-xl text-indigo-100 mb-8">
                    Join Daily Calisthenic and build the body you want, one workout at a time.
                </p>
                @guest
                    <a href="{{ route('register') }}" class="inline-block px-8 py-4 bg-white text-indigo-600 text-lg font-semibold rounded-lg hover:bg-gray-100 transition-colors">
                        Get Started Now
                    </a>
                @else
                    <a href="{{ url('/dashboard') }}" class="inline-block px-8 py-4 bg-white text-indigo-600 text-lg font-semibold rounded-lg hover:bg-gray-100 transition-colors">
                        Go to Dashboard
                    </a>
                @endguest
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-950 border-t border-gray-800 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-center text-gray-400">
                    &copy; {{ date('Y') }} Daily Calisthenic by Simon Hokesen. Progressive bodyweight training.
                </p>
            </div>
        </footer>
    </body>
</html>
