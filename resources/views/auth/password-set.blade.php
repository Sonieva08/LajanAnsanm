<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">{{ __('Mete oswa chanje modpas ou') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto px-4 space-y-4">
            @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif

            <form method="POST" action="{{ route('password.set.update') }}" class="card !p-space-lg space-y-space-md">
                @csrf
                @if ($hasPassword)
                    <div>
                        <label class="label" for="current_password">{{ __('Modpas aktyèl') }}</label>
                        <input id="current_password" type="password" name="current_password" class="input" required autocomplete="current-password">
                        <x-input-error :messages="$errors->get('current_password')" class="mt-1" />
                    </div>
                @endif
                <div>
                    <label class="label" for="password">{{ __('Nouvo modpas') }}</label>
                    <input id="password" type="password" name="password" class="input" required autocomplete="new-password">
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <label class="label" for="password_confirmation">{{ __('Konfime modpas') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
                </div>
                <button class="btn-primary w-full">{{ __('Sove modpas la') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
