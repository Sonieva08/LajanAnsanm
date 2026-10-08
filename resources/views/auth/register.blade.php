<x-guest-layout>
    <div class="grid grid-cols-2 bg-surface-container-high p-1 rounded-xl mb-space-lg font-label-lg text-label-lg text-center">
        <a href="{{ route('login') }}" class="py-3 text-on-surface-variant">{{ __('Log in') }}</a>
        <span class="py-3 rounded-lg bg-white shadow-sm text-primary-container">{{ __('Register') }}</span>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-space-md">
        @csrf
        <div>
            <label for="name" class="label">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" class="input" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>
        <div>
            <label for="password" class="label">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" class="input" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <div>
            <label for="password_confirmation" class="label">{{ __('Confirm Password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
        </div>
        <button class="btn-primary w-full">{{ __('Kreye kont') }} <span class="material-symbols-outlined">arrow_forward</span></button>
    </form>
</x-guest-layout>
