<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a
                href="{{ route('home') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-white/70 hover:text-white app-chip"
            >
                <span aria-hidden="true">←</span>
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12 app-reveal">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="app-panel rounded-3xl p-6 sm:p-10">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl sm:text-3xl font-semibold text-white">
                        Keep your account in sync
                    </h1>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="app-card app-card--stack rounded-3xl p-6 sm:p-8">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="app-card app-card--stack rounded-3xl p-6 sm:p-8">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="app-card app-card--stack rounded-3xl p-6 sm:p-8">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
