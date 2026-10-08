<x-guest-layout>
    <div class="grid grid-cols-2 bg-surface-container-high p-1 rounded-xl mb-space-lg font-label-lg text-label-lg text-center">
        <span class="py-3 rounded-lg bg-white shadow-sm text-primary-container">{{ __('Log in') }}</span>
        <a href="{{ route('register') }}" class="py-3 text-on-surface-variant">{{ __('Register') }}</a>
    </div>

    @if (session('status'))<div class="alert-ok mb-space-md">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ route('login') }}" class="space-y-space-md" x-data="{ show: false }">
        @csrf
        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required autofocus autocomplete="username" placeholder="non@imel.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <div class="flex justify-between">
                <label for="password" class="label">{{ __('Password') }}</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="font-label-md text-label-md text-primary-container">{{ __('Forgot your password?') }}</a>
                @endif
            </div>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" class="input pr-12" required autocomplete="current-password">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-3 flex items-center text-on-surface-variant" tabindex="-1">
                    <span class="material-symbols-outlined" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <label class="flex items-center gap-2 text-on-surface-variant">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-primary-container focus:ring-primary-container/30">
            {{ __('Remember me') }}
        </label>

        <button class="btn-primary w-full">{{ __('Konekte sou kont ou') }} <span class="material-symbols-outlined">arrow_forward</span></button>
    </form>
</x-guest-layout>
