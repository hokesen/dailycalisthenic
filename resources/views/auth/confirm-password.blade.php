<x-guest-layout>
    <div class="mb-6">
        <h1 class="app-auth-heading">{{ __('Confirm your password') }}</h1>
        <p class="app-auth-subtitle">{{ __('This protects sensitive account actions.') }}</p>
    </div>

    <div class="mb-4 text-sm text-white/70">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex justify-end">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
