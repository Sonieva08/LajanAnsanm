<x-guest-layout>
    @php
        $t = $invitation->tontine;
        $period = $t->period_type === 'monthly' ? __('Chak mwa') : __('Chak semèn');
        $tours = $t->members()->where('is_active', true)->count();
    @endphp

    <div class="flex flex-col gap-space-lg text-center">
        <div class="w-16 h-16 rounded-full bg-surface-container-high text-primary flex items-center justify-center mx-auto shadow-sm">
            <span class="material-symbols-outlined text-[32px]" style="font-variation-settings:'FILL' 1">mail</span>
        </div>

        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-wider text-primary/70">{{ __('Envitasyon ofisyèl') }}</p>
            <h1 class="font-headline-md text-headline-md text-primary mt-1">{{ __('Ou resevwa yon envitasyon') }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">
                <strong class="text-on-surface">{{ $organizer ?? __('Òganizatè a') }}</strong> {{ __('envite w rejwenn sòl') }}
                <strong class="text-primary">{{ $t->name }}</strong>.
            </p>
        </div>

        <div class="rounded-xl bg-surface-container-low p-space-md text-left space-y-space-sm font-body-md text-body-md">
            <div class="flex items-center justify-between gap-space-sm">
                <span class="text-on-surface-variant">{{ __('Kotizasyon') }}</span>
                <span class="tabular-nums font-semibold">{{ number_format((float) $t->amount_per_period, 0, '.', ',') }} {{ config('lajanansanm.currency') }} <span class="font-normal text-on-surface-variant">{{ $period }}</span></span>
            </div>
            <div class="flex items-center justify-between gap-space-sm">
                <span class="text-on-surface-variant">{{ __('Kantite manm') }}</span>
                <span class="tabular-nums font-semibold">{{ $tours }}</span>
            </div>
        </div>

        <p class="font-body-sm text-body-sm text-on-surface-variant">
            {{ __('Lè ou aksepte, ou gen aksè pèmanan nan sòl la jiska li fèmen. Ou pa wè lis manm yo.') }}
        </p>

        @if ($alreadyMember)
            <a href="{{ route('memberships.index') }}" class="btn-primary w-full">{{ __('Wè sòl mwen yo') }}</a>
        @else
            @if ($errors->has('invite'))
                <div class="alert-err text-left">{{ $errors->first('invite') }}</div>
            @endif
            <form method="POST" action="{{ route('invitations.accept', $token) }}" class="space-y-space-md text-left">
                @csrf
                <div>
                    <label for="member-name" class="label">{{ __('Non ou') }}</label>
                    <input id="member-name" name="name" type="text" required maxlength="120"
                           value="{{ old('name', $memberName) }}" class="input" autocomplete="name">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <label class="flex items-start gap-2 font-body-sm text-body-sm text-on-surface">
                    <input type="checkbox" name="consent" value="1" required @checked(old('consent'))
                           class="mt-1 rounded border-slate-300 text-primary-container">
                    <span>{{ __('Mwen dakò pou yo konsève enfòmasyon manm mwen yo pou jere sòl sa a.') }}</span>
                </label>
                <x-input-error :messages="$errors->get('consent')" />
                <button class="btn-primary w-full">{{ __('Aksepte envitasyon an') }}</button>
            </form>
        @endif
    </div>
</x-guest-layout>
