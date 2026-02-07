<section>
    <header>
        <h2 class="app-section-title">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-white/60">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" class="text-white/70" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full app-field placeholder:text-white/40" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-white/70" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full app-field placeholder:text-white/40" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-white/70 hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 focus:ring-offset-transparent">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-emerald-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="timezone" :value="__('Timezone')" class="text-white/70" />
            <select
                id="timezone"
                name="timezone"
                class="mt-1 block w-full rounded-md app-field focus:outline-none"
            >
                <option value="">{{ __('Select a timezone') }}</option>
                @foreach($timezones as $timezone)
                    <option value="{{ $timezone }}" @selected(old('timezone', $user->timezone) === $timezone)>
                        {{ $timezone }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button class="bg-emerald-500 hover:bg-emerald-400 focus:ring-emerald-400">
                {{ __('Save') }}
            </x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-white/60"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
