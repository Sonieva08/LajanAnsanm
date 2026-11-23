<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-on-surface">{{ __('Envite yon moun') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 flex flex-col gap-space-lg">

            <div class="flex items-center gap-space-sm text-on-surface-variant font-label-md text-label-md">
                <span class="material-symbols-outlined text-[18px]">group</span>
                <span>{{ $tontine->name }}</span>
                <span class="inline-flex items-center gap-1 ml-auto font-label-sm"><span class="w-2 h-2 rounded-full {{ $tontine->status === 'draft' ? 'bg-on-tertiary-container' : 'bg-outline' }}"></span>{{ $tontine->status === 'draft' ? __('Manm aktif') : __('Sòl deja lanse') }}</span>
            </div>

            @if ($errors->any())
                <div class="alert-err">{{ $errors->first() }}</div>
            @endif

            @if (session('invite_url'))
                <section class="card !p-space-md space-y-space-sm"
                         x-data="{ copied: false, link: @js(session('invite_url')) }">
                    <p class="font-label-md text-label-md">{{ __('Lyen envitasyon an (montre yon sèl fwa):') }}</p>
                    <div class="flex items-center gap-2 p-2 rounded-xl bg-surface-container-lowest shadow-sm">
                        <input class="flex-1 min-w-0 bg-transparent font-label-md text-label-md focus:outline-none truncate" readonly :value="link" @focus="$el.select()">
                        <button type="button" class="h-9 px-3 rounded-lg bg-surface-container-high text-primary font-label-sm text-label-sm shrink-0"
                                @click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-text="copied ? '{{ __('Kopye!') }}' : '{{ __('Kopye lyen') }}'">{{ __('Kopye lyen') }}</span>
                        </button>
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Lyen sa a itilize yon sèl fwa, epi li valab :n jou.', ['n' => $days]) }}</p>
                    <div class="flex flex-col sm:flex-row gap-space-sm">
                        <a href="{{ session('invite_wa') }}" target="_blank" rel="noopener noreferrer" class="btn-primary flex-1">{{ __('Voye sou WhatsApp') }}</a>
                        <a href="{{ session('invite_sms') }}" class="btn-secondary flex-1">{{ __('Voye pa SMS') }}</a>
                    </div>
                </section>
            @endif

            @if (! $hasFeature)
                <section class="card !p-space-lg space-y-space-sm">
                    <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Fonksyon sa a disponib sou yon plan ki gen espas manm.') }}</p>
                    <a href="{{ route('plans') }}" class="btn-primary">{{ __('Wè plan yo') }}</a>
                </section>
            @elseif ($tontine->status !== 'draft')
                <section class="card !p-space-lg flex items-start gap-space-md bg-surface-container-high">
                    <span class="material-symbols-outlined text-outline text-[28px]">lock</span>
                    <p class="font-body-md text-body-md text-on-surface">{{ __('Kalandriye a deja lanse. Ou pa ka ajoute moun ankò.') }}</p>
                </section>
            @elseif (! $canAdd)
                <section class="card !p-space-lg flex items-start gap-space-md">
                    <span class="material-symbols-outlined text-secondary text-[28px]">group_off</span>
                    <p class="font-body-md text-body-md text-on-surface">{{ __('Ou rive nan limit manm pou plan ou an (:n / :m).', ['n' => $memberCount, 'm' => $maxMembers]) }}</p>
                </section>
            @else
                <form method="POST" action="{{ route('invitations.store', $tontine) }}" class="card !p-space-lg space-y-space-md">
                    @csrf
                    <div>
                        <h3 class="font-title-lg text-title-lg">{{ __('Envite yon nouvo manm') }}</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Lyen an fonksyone jiska sòl la kòmanse. Chak lyen itilize yon sèl fwa.') }}</p>
                    </div>
                    <div>
                        <label class="label" for="phone">{{ __('Nimewo WhatsApp') }}</label>
                        <input id="phone" name="phone" type="tel" required value="{{ old('phone') }}" class="input" placeholder="+509 3400 0000">
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>
                    <div>
                        <label class="flex items-start gap-2 font-body-sm text-body-sm text-on-surface">
                            <input type="checkbox" name="contact_permission" value="1" required @checked(old('contact_permission')) class="mt-1 rounded border-slate-300 text-primary-container">
                            <span>{{ __('Mwen gen pèmisyon pou m sèvi ak nimewo sa a pou voye envitasyon an.') }}</span>
                        </label>
                        <x-input-error :messages="$errors->get('contact_permission')" class="mt-1" />
                    </div>
                    <div class="flex justify-end">
                        <button class="btn-primary">{{ __('Kreye envitasyon') }}</button>
                    </div>
                </form>
            @endif

            <a href="{{ route('tontines.show', $tontine) }}" class="font-label-md text-label-md text-primary hover:underline">{{ __('Retounen sou sòl la') }}</a>
        </div>
    </div>
</x-app-layout>
