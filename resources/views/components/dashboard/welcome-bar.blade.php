@props(['user', 'hasPracticed', 'streak', 'disciplines', 'selectedDiscipline'])

<div class="mb-6" x-data="{ showUserMenu: false }">
    <div class="px-1 py-2 sm:px-0 sm:py-0">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="dashboard-brand">
                    <div class="dashboard-brand-lockup">
                        <div class="dashboard-brand-mark" aria-hidden="true">
                            <span class="dashboard-brand-kicker">Daily</span>
                        </div>
                        <form method="POST" action="{{ route('discipline.update') }}" class="dashboard-discipline-form">
                            @csrf
                            <label class="sr-only" for="discipline-switcher">Discipline</label>
                            <div class="dashboard-discipline-shell">
                                <select
                                    id="discipline-switcher"
                                    name="discipline"
                                    onchange="this.form.submit()"
                                    class="dashboard-discipline-select"
                                >
                                    @foreach ($disciplines as $discipline => $definition)
                                        @continue(in_array($discipline, ['meditation', 'lifting'], true))

                                        <option value="{{ $discipline }}" @selected($selectedDiscipline === $discipline) class="bg-slate-900 text-white">
                                            {{ $definition['label'] }}{{ ($definition['status'] ?? 'planned') !== 'live' ? ' (Soon)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="dashboard-discipline-icon" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M6 9l6 6 6-6" />
                                    </svg>
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 sm:gap-4">
                <x-today-status-badge :hasPracticed="$hasPracticed" />

                <div class="relative" @click.outside="showUserMenu = false">
                    <button @click="showUserMenu = !showUserMenu" class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white/80 hover:text-white bg-white/10 rounded-lg transition-colors border border-white/10">
                        <span class="hidden sm:inline">{{ $user->name }}</span>
                        <span class="sm:hidden">Menu</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="showUserMenu" x-transition x-cloak class="absolute right-0 mt-2 w-48 app-panel rounded-xl z-50">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-white/80 hover:bg-white/10 rounded-t-lg">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-white/80 hover:bg-white/10 rounded-b-lg">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
