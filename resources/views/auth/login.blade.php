<x-guest-layout>
    @if (session('status'))
        <div class="alert-ok mb-space-md">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-space-md" x-data="{ show: false }">
        @csrf

        <div class="flex flex-col gap-1.5 text-left">
            <label class="label" for="email">{{ __('Adrès imèl') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="input" placeholder="{{ __('egzanp: non@imel.com') }}">
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="flex flex-col gap-1.5 text-left">
            <label class="label" for="password">{{ __('Modpas') }}</label>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password"
                       class="input pr-12" placeholder="••••••••">
                <button type="button" @click="show = !show" aria-label="{{ __('Chanje vizibilite modpas la') }}"
                        class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container">
                    <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
            @if (Route::has('password.request'))
                <div class="flex justify-end">
                    <a href="{{ route('password.request') }}" class="font-label-sm text-label-sm text-primary hover:underline">{{ __('Ou bliye modpas ou?') }}</a>
                </div>
            @endif
        </div>

        <button class="btn-primary w-full">{{ __('Konekte') }}</button>
    </form>

    @include('partials.google-button')

    <p class="mt-space-md text-center font-body-md text-body-md text-on-surface-variant">
        {{ __('Pa gen kont?') }}
        <a href="{{ route('register') }}" class="font-label-lg text-label-lg text-primary hover:underline ml-1">{{ __('Kreye yon kont') }}</a>
    </p>
</x-guest-layout>
