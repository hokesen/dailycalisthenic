@props(['user', 'hasPracticed', 'streak', 'potentialStreak'])

<!-- Welcome Bar with Title, Streak, and User Dropdown -->
<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg mb-6" x-data="{ showUserMenu: false }">
    <div class="p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Left: Title and Welcome -->
            <div class="flex items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">Daily Calisthenics</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Welcome, {{ $user->name }}!</p>
                </div>
            </div>

            <!-- Right: Today Status, Streak, and User Menu -->
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Today's Status -->
                <x-today-status-badge :hasPracticed="$hasPracticed" />

                <!-- Streak -->
                <x-streak-badge
                    :count="$streak"
                    :potentialStreak="$hasPracticed ? null : $potentialStreak"
                />

                <!-- User Dropdown -->
                <div class="relative" @click.outside="showUserMenu = false">
                    <button @click="showUserMenu = !showUserMenu" class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 bg-gray-100 dark:bg-gray-700 rounded-lg transition-colors">
                        <span class="hidden sm:inline">{{ $user->name }}</span>
                        <span class="sm:hidden">Menu</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="showUserMenu" x-transition x-cloak class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-lg">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-b-lg">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
