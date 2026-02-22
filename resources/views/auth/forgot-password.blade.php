<x-guest-layout>
    <div class="mb-6">
        <h1 class="app-auth-heading">{{ __('Reset your password') }}</h1>
        <p class="app-auth-subtitle">
            {{ __('Enter your email and we will send a secure reset link.') }}
        </p>
    </div>

    <div class="mb-4 text-sm text-white/70">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <x-auth-session-status class="mb-4 text-sm text-emerald-300" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="flex justify-end">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
