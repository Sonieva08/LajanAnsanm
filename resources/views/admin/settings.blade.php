<x-admin-layout active="settings" :title="__('Paramèt')">
    @php
        $on = (bool) old('enabled', $maintenance['enabled']);
        $defaultMsg = __('LajanAnsanm ap amelyore. Done ou yo an sekirite. Nou tounen talè.');
        $updatedAt = $maintenance['updated_at'] ? \Illuminate\Support\Carbon::parse($maintenance['updated_at'])->timezone(config('app.timezone')) : null;
    @endphp

    <div class="mb-space-lg space-y-space-sm">
        <nav class="flex items-center gap-1 font-label-sm text-label-sm uppercase tracking-wider text-outline">
            <span>{{ __('Konsòl pwopriyetè') }}</span><span class="material-symbols-outlined text-[16px]">chevron_right</span><span class="text-primary font-bold">{{ __('Paramèt') }}</span>
        </nav>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-headline-lg text-headline-lg text-primary">{{ __('Paramèt jeneral ak kontwòl platfòm nan') }}</h1>
                <p class="text-on-surface-variant max-w-2xl">{{ __('Reglaj global platfòm lan. Chanjman yo aplike imedyatman.') }}</p>
            </div>
            @if ($maintenance['enabled'])
                <span class="inline-flex items-center gap-2 bg-surface-container-high px-4 py-1.5 rounded-full font-label-sm text-label-sm">
                    <span class="w-2.5 h-2.5 rounded-full bg-secondary-container animate-pulse"></span> {{ __('Mòd antretyen anplas') }}
                </span>
            @endif
        </div>

        <div class="flex items-center gap-1 bg-surface-container-low p-1.5 rounded-xl overflow-x-auto">
            <span class="flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-container-lowest text-primary font-title-md text-title-md shadow-sm whitespace-nowrap">
                <span class="material-symbols-outlined text-secondary text-[18px]">construction</span> {{ __('Mòd antretyen') }}
            </span>
            @foreach ([__('Sekirite ak aksè'), __('Peman ak API'), __('Notifikasyon SMS')] as $soon)
                <span class="flex items-center gap-2 px-4 py-2 rounded-lg text-slate-400 font-title-md text-title-md whitespace-nowrap cursor-not-allowed" title="{{ __('Byento') }}">
                    {{ $soon }} <span class="badge-muted !py-0 !px-2 text-[10px]">{{ __('Byento') }}</span>
                </span>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-12 gap-space-md items-start"
         x-data="{ on: {{ $on ? 'true' : 'false' }}, msg: @js(old('message', $maintenance['message'] ?? '')), endsAt: @js(old('ends_at', $maintenance['ends_at'] ?? '')),
                   fallback: @js($defaultMsg),
                   get endsLabel() { return this.endsAt ? this.endsAt.slice(8,10) + '/' + this.endsAt.slice(5,7) + '/' + this.endsAt.slice(0,4) + ' ' + this.endsAt.slice(11,16) : ''; } }">

        <form method="POST" action="{{ route('admin.settings.maintenance') }}" class="lg:col-span-7 card !p-0 overflow-hidden relative">
            @csrf
            <div class="h-1.5 bg-secondary-container"></div>
            <div class="p-space-lg space-y-space-lg">
                <div class="flex items-start justify-between gap-space-md">
                    <div class="flex items-start gap-space-md">
                        <div class="p-2 rounded-xl bg-error-container text-on-error-container"><span class="material-symbols-outlined">power_settings_new</span></div>
                        <div>
                            <h2 class="font-title-lg text-title-lg text-primary font-bold">{{ __('Mòd antretyen platfòm') }}</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">{{ __('Lè l aktif, tout moun wè yon paj antretyen (503) sof ou menm. Paj koneksyon an rete louvri pou ou ka antre.') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="enabled" value="1" class="sr-only peer" x-model="on" @checked($on)>
                            <span class="w-14 h-8 bg-surface-container-highest rounded-full peer-checked:bg-secondary-container shadow-inner transition"></span>
                            <span class="absolute left-1 top-1 w-6 h-6 rounded-full bg-white shadow transition peer-checked:translate-x-6"></span>
                        </label>
                        <span class="font-label-sm text-label-sm font-bold uppercase tracking-wider" :class="on ? 'text-secondary' : 'text-on-tertiary-container'" x-text="on ? '{{ __('Aktif') }}' : '{{ __('Dezaktive') }}'"></span>
                    </div>
                </div>

                <div class="rounded-xl p-space-md flex items-center gap-space-sm shadow-sm" :class="on ? 'bg-error-container text-on-error-container' : 'bg-surface-container'">
                    <span class="material-symbols-outlined shrink-0" :class="on ? 'text-error' : 'text-on-tertiary-container'" x-text="on ? 'warning' : 'check_circle'"></span>
                    <div>
                        <p class="font-label-md text-label-md font-bold" x-text="on ? '{{ __('Sit la ap fèmen pou itilizatè yo apre ou sove.') }}' : '{{ __('Sit la ouvè pou tout moun.') }}'"></p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Sèl pwopriyetè a ki rete konekte ka itilize sit la pandan antretyen an.') }}</p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="message" class="font-label-md text-label-md font-bold">{{ __('Mesaj pou itilizatè yo (opsyonèl)') }}</label>
                        <span class="font-label-sm text-label-sm text-outline" x-text="msg.length + ' / 300 {{ __('karaktè') }}'"></span>
                    </div>
                    <textarea id="message" name="message" rows="4" maxlength="300" x-model="msg" class="input !min-h-0 resize-none bg-surface-container-low"
                              placeholder="{{ __('Egzanp: Nou ap amelyore sistèm nan. Nou tounen nan 30 minit.') }}">{{ old('message', $maintenance['message']) }}</textarea>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ __('Mesaj sa a piblik. Pa pwomèt sa ou pa sèten (egzanp: "pa gen anyen ki pèdi") si ou ap fè yon migrasyon.') }}</p>
                    <x-input-error :messages="$errors->get('message')" class="mt-1" />
                </div>

                <div class="grid md:grid-cols-2 gap-space-md">
                    <div>
                        <label for="ends_at" class="label">{{ __('Lè nou espere tounen (opsyonèl)') }}</label>
                        <input id="ends_at" type="datetime-local" name="ends_at" x-model="endsAt" class="input bg-surface-container-low" value="{{ old('ends_at', $maintenance['ends_at']) }}">
                        <p class="font-body-sm text-body-sm text-outline mt-1">{{ __('Se yon endikasyon sèlman. Sit la pa rouvri poukont li : dezaktive mòd la ou menm.') }}</p>
                        <x-input-error :messages="$errors->get('ends_at')" class="mt-1" />
                    </div>
                    <div>
                        <span class="label">{{ __('Aksè pwopriyetè') }}</span>
                        <div class="flex items-center justify-between bg-surface-container-low px-4 py-3 rounded-xl">
                            <div><p class="font-label-md text-label-md font-semibold">{{ auth()->user()->name }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Kont ou a pa bloke pandan antretyen an.') }}</p></div>
                            <span class="material-symbols-outlined text-on-tertiary-container">verified_user</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-low/60 p-space-lg flex flex-col sm:flex-row sm:items-center justify-between gap-space-md">
                <p class="flex items-center gap-2 font-body-sm text-body-sm text-on-surface-variant">
                    <span class="material-symbols-outlined text-outline text-[18px]">history</span>
                    @if ($maintenance['updated_by'] && $updatedAt)
                        {{ __('Dènye modifikasyon pa') }} <strong class="text-on-surface">{{ $maintenance['updated_by'] }}</strong> · {{ $updatedAt->format('d/m/Y H:i') }}
                    @else
                        {{ __('Poko gen modifikasyon.') }}
                    @endif
                </p>
                <div class="flex items-center gap-space-sm">
                    <a href="{{ route('admin.settings') }}" class="px-4 py-2 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container">{{ __('Retounen san sove') }}</a>
                    <button class="btn-primary"><span class="material-symbols-outlined text-[18px]">save</span> {{ __('Sove modifikasyon yo') }}</button>
                </div>
            </div>
        </form>

        {{-- Aperçu en direct --}}
        <div class="lg:col-span-5 space-y-space-sm">
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 font-title-md text-title-md text-primary font-bold"><span class="material-symbols-outlined text-[20px]">visibility</span> {{ __('Aperi an dirèk') }}</span>
                <span class="badge bg-error-container text-on-error-container"><span class="w-2 h-2 rounded-full bg-error animate-ping"></span> {{ __('An tan reyèl') }}</span>
            </div>
            <div class="card !p-space-sm shadow-md">
                <div class="flex items-center justify-between bg-surface-container-low px-3 py-1.5 rounded-lg mb-space-sm">
                    <span class="flex gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-error"></i><i class="w-2.5 h-2.5 rounded-full bg-outline-variant"></i><i class="w-2.5 h-2.5 rounded-full bg-on-tertiary-container"></i></span>
                    <span class="font-label-sm text-label-sm text-on-surface-variant">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'lajanansanm' }}</span>
                    <span class="material-symbols-outlined text-outline text-[16px]">lock</span>
                </div>
                <div class="bg-background rounded-xl p-space-lg flex flex-col items-center text-center min-h-[380px] justify-center shadow-inner">
                    <div class="w-24 h-24 rounded-full bg-surface-container-high flex items-center justify-center relative mb-space-md">
                        <span class="material-symbols-outlined text-[48px] text-primary" style="animation: lajan-spin 12s linear infinite;">settings</span>
                        <span class="absolute -bottom-1 -right-1 w-9 h-9 rounded-full bg-secondary text-on-secondary flex items-center justify-center shadow-md"><span class="material-symbols-outlined text-[18px]">build</span></span>
                    </div>
                    <p class="font-headline-md text-headline-md text-primary font-bold mb-1">{{ __('Nou ap fè antretyen') }}</p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant max-w-xs mb-space-md" x-text="msg.trim() || fallback"></p>
                    <div x-show="endsLabel" x-cloak class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-surface-container text-primary font-label-sm text-label-sm mb-space-md">
                        <span class="material-symbols-outlined text-[15px]">schedule</span> {{ __('Nou espere tounen') }} : <strong x-text="endsLabel"></strong>
                    </div>
                    <span class="w-full max-w-xs py-2 rounded-xl bg-surface-container-high text-primary font-label-md text-label-md flex items-center justify-center gap-1"><span class="material-symbols-outlined text-[18px]">refresh</span> {{ __('Rechaje paj la') }}</span>
                </div>
            </div>
            <div class="flex items-start gap-2 bg-surface-container-low p-space-sm rounded-xl font-body-sm text-body-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-outline text-[20px]">info</span>
                {{ __('Aperi sa a montre sa vizitè yo ap wè pandan mòd antretyen an aktif.') }}
            </div>
        </div>
    </div>
</x-admin-layout>
