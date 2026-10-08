<x-app-layout>
    @php $cur = config('lajanansanm.currency'); @endphp
    <x-page-header :back="route('tontines.show', $tontine)" :title="__('Kalandriye tou yo')" :subtitle="$tontine->name . ' · ' . __('Kòl') . ' ' . $tontine->current_round" />

    <div class="space-y-space-md">
        @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->has('cycles'))<div class="alert-err">{{ $errors->first('cycles') }}</div>@endif

        @if ($tontine->status === 'completed')
            <div class="card !p-space-lg space-y-space-md ring-2 ring-emerald-400">
                <h3 class="font-title-lg text-title-lg">🎉 {{ __('Kòl') }} {{ $tontine->current_round }} {{ __('fini!') }}</h3>
                <p class="text-on-surface-variant">{{ __('Tout manm yo resevwa lajan yo. Ou ka relanse yon nouvo kòl: ou pral ka ajoute oswa retire manm anvan ou jenere kalandriye a.') }}</p>

                @if (\App\Support\Entitlements::for(auth()->user())->has('restart_round'))
                <form method="POST" action="{{ route('tontines.cycles.restart', $tontine) }}" class="space-y-space-md"
                      onsubmit="return confirm('{{ __('Relanse yon nouvo kòl?') }}')">
                    @csrf
                    <div>
                        <label for="start_date" class="label">{{ __('Dat premye peman nouvo kòl la') }}</label>
                        <input id="start_date" type="date" name="start_date" class="input" required
                               min="{{ now()->format('Y-m-d') }}" value="{{ old('start_date', $suggestedStart) }}">
                        <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                    </div>
                    <label class="flex items-start gap-2 text-on-surface-variant">
                        <input type="checkbox" name="shuffle" value="1" class="mt-1 rounded border-slate-300 text-primary-container">
                        {{ __('Melanje lòd benefisyè yo o aza') }}
                    </label>
                    <button class="btn-primary w-full"><span class="material-symbols-outlined">autorenew</span> {{ __('Relanse yon nouvo kòl') }}</button>
                </form>
                @else
                <div class="rounded-xl bg-primary-fixed/50 p-space-md flex items-center justify-between gap-3" data-restart-locked>
                    <p class="font-body-md text-body-md">{{ __('Relanse yon nouvo sik disponib sou yon plan peyan.') }}</p>
                    <a href="{{ route('plans') }}" class="btn-primary btn-sm">{{ __('Wè plan yo') }}</a>
                </div>
                @endif
            </div>
        @endif

        @if ($cycles->isEmpty())
            <div class="card !p-space-lg space-y-space-md">
                <h3 class="font-title-lg text-title-lg">{{ __('Pare pou kòmanse?') }}</h3>
                <div class="grid grid-cols-2 gap-3 tabular-nums">
                    <div class="bg-surface-container-low rounded-xl p-space-sm"><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Manm aktif') }}</p><p class="font-title-lg text-title-lg">{{ $activeMembers }}</p></div>
                    <div class="bg-surface-container-low rounded-xl p-space-sm"><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Kantite tou') }}</p><p class="font-title-lg text-title-lg">{{ $activeMembers }}</p></div>
                    <div class="bg-surface-container-low rounded-xl p-space-sm"><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Lajan chak benefisyè resevwa') }}</p><p class="font-title-lg text-title-lg text-emerald-600">{{ number_format($tontine->netPotAmount(), 0, '.', ',') }} {{ $cur }}</p></div>
                    <div class="bg-surface-container-low rounded-xl p-space-sm"><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Premye dat limit') }}</p><p class="font-title-lg text-title-lg">{{ $tontine->start_date->format('d/m/Y') }}</p></div>
                </div>
                <a href="{{ route('tontines.members.index', $tontine) }}" class="btn-secondary w-full">
                    <span class="material-symbols-outlined">group_add</span> {{ __('Ajoute oswa modifye manm yo anvan') }}
                </a>
                @if ($tontine->hasFee())
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Kòmisyon òganizatè') }} : {{ (float) $tontine->organizer_fee_percent }}% — {{ __('soustrè sou chak vèsman.') }}</p>
                @endif
                <div class="flex gap-2 bg-amber-50 text-amber-800 p-space-sm rounded-xl font-body-sm text-body-sm">
                    <span class="material-symbols-outlined text-[18px]">warning</span>
                    {{ __('Apre sa, lis manm yo ap bloke. Ou ka anile sèlman si pa gen okenn peman anrejistre.') }}
                </div>
                @if ($activeMembers >= 2)
                    <form method="POST" action="{{ route('tontines.cycles.generate', $tontine) }}" onsubmit="return confirm('{{ __('Jenere tou yo epi kòmanse tontine a?') }}')">
                        @csrf
                        <button class="btn-primary w-full"><span class="material-symbols-outlined">rocket_launch</span> {{ __('Jenere tou yo') }}</button>
                    </form>
                @else
                    <p class="text-secondary">{{ __('Ou bezwen omwen 2 manm pou kòmanse tontine a.') }}</p>
                    <a href="{{ route('tontines.members.index', $tontine) }}" class="btn-secondary w-full">{{ __('Jere manm yo') }}</a>
                @endif
            </div>
        @else
            <div class="space-y-space-sm">
                @foreach ($cycles as $cycle)
                    @php
                        $late = $cycle->isLate();
                        $current = $cycle->status === 'open';
                        $badge = match (true) {
                            $late => ['badge-late', __('An reta')],
                            $current => ['badge-wait', __('Ouvè')],
                            $cycle->status === 'closed' => ['badge-paid', __('Fèmen')],
                            default => ['badge-muted', __('Ap vini')],
                        };
                    @endphp
                    <div class="card !p-space-sm flex items-center gap-3 {{ $current ? 'ring-2 ring-primary-container' : '' }}">
                        <span class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center font-title-md text-title-md
                                     {{ $cycle->status === 'pending' ? 'bg-primary-fixed text-primary-container' : 'bg-primary-container text-on-primary' }}">{{ $cycle->cycle_number }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-title-md text-title-md truncate">{{ $cycle->beneficiary?->name ?? '—' }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">
                                {{ $cycle->deadline->format('d/m/Y') }} · {{ $cycle->contributions_count }}/{{ $activeMembers }} {{ __('peye') }}
                            </p>
                        </div>
                        <span class="{{ $badge[0] }}">{{ $badge[1] }}</span>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('tontines.cycles.reset', $tontine) }}" class="text-center pt-space-sm" onsubmit="return confirm('{{ __('Anile kalandriye a?') }}')">
                @csrf @method('DELETE')
                <button class="font-label-md text-label-md text-secondary underline">{{ __('Anile kalandriye a') }}</button>
            </form>
        @endif

        @if ($history->isNotEmpty())
            <div class="pt-space-md space-y-space-sm">
                <h2 class="font-title-md text-title-md text-on-surface-variant">{{ __('Istwa kòl yo') }}</h2>
                @foreach ($history->sortKeysDesc() as $round => $h)
                    <div class="card !p-space-sm flex items-center justify-between gap-3">
                        <div>
                            <p class="font-title-md text-title-md">{{ __('Kòl') }} {{ $round }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">
                                {{ $h['from']->format('d/m/Y') }} → {{ $h['to']->format('d/m/Y') }} · {{ $h['cycles'] }} {{ __('tou') }}
                            </p>
                        </div>
                        <span class="badge-paid tabular-nums">{{ number_format($h['paid_out'], 0, '.', ',') }} {{ config('lajanansanm.currency') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
