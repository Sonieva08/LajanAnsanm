<x-guest-layout>
    <div class="flex flex-col gap-space-lg text-center">
        <div class="w-16 h-16 rounded-full bg-primary-fixed text-primary flex items-center justify-center mx-auto shadow-sm">
            <span class="material-symbols-outlined text-[32px]">check_circle</span>
        </div>

        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-wider text-primary/70">{{ __('Enskripsyon fini') }}</p>
            <h1 class="font-headline-md text-headline-md text-primary mt-1">{{ __('Ou se manm kounye a') }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">
                {{ __(':name antre nan sòl :t.', ['name' => $member->name, 't' => $member->tontine->name]) }}
            </p>
        </div>

        <div class="rounded-xl bg-surface-container-low p-space-md space-y-space-sm text-left">
            <p class="font-label-lg text-label-lg">{{ __('Sove lyen pèsonèl sa a') }}</p>
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                {{ __('Lyen sa a ba ou aksè pèmanan nan pòtay manm nan jiskaske sòl la fèmen. Pa pataje l ak lòt moun.') }}
            </p>
            <div class="flex items-center gap-2">
                <input id="member-portal-link" class="input min-w-0" readonly value="{{ $portalUrl }}" aria-label="{{ __('Lyen pèsonèl pòtay manm ou an') }}">
                <button type="button" class="btn-secondary shrink-0" x-data
                        @click="navigator.clipboard.writeText(document.getElementById('member-portal-link').value)">
                    {{ __('Kopye lyen') }}
                </button>
            </div>
        </div>

        <a href="{{ $portalUrl }}" class="btn-primary w-full">{{ __('Antre nan pòtay manm nan') }}</a>
    </div>
</x-guest-layout>
