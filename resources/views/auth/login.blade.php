<x-guest-layout>
    <div class="mb-6">
        <h1 class="app-auth-heading">{{ __('Welcome back') }}</h1>
        <p class="app-auth-subtitle">{{ __('Log in to continue your daily practice.') }}</p>
    </div>

    <x-auth-session-status class="mb-4 text-sm text-emerald-300" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="block">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-white/70">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="h-4 w-4 rounded border-white/30 bg-transparent text-emerald-500 focus:ring-emerald-400"
                    name="remember"
                >
                <span>{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            @if (Route::has('password.request'))
                <a class="app-link text-sm" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
