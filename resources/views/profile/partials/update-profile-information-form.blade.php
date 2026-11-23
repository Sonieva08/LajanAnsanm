<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information. Changing your email requires your current password and confirmation at the new address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            <div class="mt-4">
                <x-input-label for="current_password" :value="__('Current password (required to change email)')" />
                <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error class="mt-2" :messages="$errors->get('current_password')" />
            </div>

            @if ($user->pending_email)
                <div class="mt-3 text-sm text-amber-700">
                    <p>{{ __('Email change pending confirmation at :email.', ['email' => $user->pending_email]) }}</p>
                    <button form="resend-email-change" class="mt-2 underline">{{ __('Resend confirmation link') }}</button>
                    @if (session('status') === 'email-change-link-sent')
                        <p class="mt-2 text-green-600">{{ __('A new confirmation link has been sent.') }}</p>
                    @endif
                </div>
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail() && ! $user->pending_email)
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                    @if (session('status') === 'verification-email-failed')
                        <p class="mt-2 font-medium text-sm text-red-600">
                            {{ __("We couldn't send the verification email. Please check the mail settings and try again later.") }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
            @if (session('status') === 'email-change-pending')
                <p class="text-sm text-green-600">{{ __('A confirmation link was sent to the new email address. Your current email remains active until you confirm it.') }}</p>
            @endif
            @if (session('status') === 'email-change-completed')
                <p class="text-sm text-green-600">{{ __('Your email address has been changed and verified.') }}</p>
            @endif
        </div>
    </form>

    @if ($user->pending_email)
        <form id="resend-email-change" method="post" action="{{ route('profile.email.resend') }}">
            @csrf
        </form>
    @endif
</section>
