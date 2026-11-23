<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Enter the six-digit code we sent to your email address to verify your account.') }}
    </div>

    @if (session('status') == 'verification-code-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('A new verification code has been sent to your email address.') }}
        </div>
    @endif
    @if (session('status') == 'verification-email-failed')
        <div class="mb-4 font-medium text-sm text-red-600">
            {{ __("We couldn't send the verification email. Please check the mail settings and try again later.") }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.code') }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <x-input-label for="code" :value="__('Verification code')" />
            <x-text-input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
        <x-primary-button>{{ __('Verify email') }}</x-primary-button>
    </form>

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend verification code') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
