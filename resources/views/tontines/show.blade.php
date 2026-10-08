<x-app-layout>
    @php
        $cur = config('lajanansanm.currency');
        $badge = match ($tontine->status) {
            'active' => ['badge-paid', __('An kou')],
            'completed' => ['badge-muted', __('Fini')],
            'paused' => ['badge-wait', __('Sispann')],
            default => ['badge-info', __('Poko kòmanse')],
        };
        $c = $currentCycle;
        $late = $c?->isLate() ?? false;
        $total = $tontine->activeMembersCount();
        $paid = $c?->contributions_count ?? 0;
        $percent = $total > 0 ? (int) round($paid / $total * 100) : 0;
        $collected = (float) ($c?->contributions_sum_amount ?? 0);
        $cyclesTotal = $tontine->roundCycles()->count();
    @endphp

    <x-page-header :back="route('tontines.index')" :title="$tontine->name">
        <a href="{{ route('tontines.edit', $tontine) }}" class="w-11 h-11 flex items-center justify-center rounded-full bg-surface-container-lowest shadow-sm" aria-label="{{ __('Edit') }}">
            <span class="material-symbols-outlined">settings</span>
        </a>
    </x-page-header>

    @if (session('status'))<div class="alert-ok mb-space-md">{{ session('status') }}</div>@endif

    <div class="space-y-space-lg">
        <div class="flex flex-wrap gap-2">
            <span class="{{ $badge[0] }}">{{ $badge[1] }}</span>
            <span class="badge-info">{{ $tontine->period_type === 'weekly' ? __('Chak semèn') : __('Chak mwa') }}</span>
            <span class="badge-muted tabular-nums">{{ number_format($tontine->amount_per_period, 0, '.', ',') }} {{ $cur }} / {{ __('moun') }}</span>
            <span class="badge-muted">{{ __('Kòmansman') }} {{ $tontine->start_date->format('d/m/Y') }}</span>
            @if ($tontine->hasFee())<span class="badge-muted">{{ __('Kòmisyon') }} {{ (float) $tontine->organizer_fee_percent }}%</span>@endif
        </div>

        {{-- Tour en cours --}}
        @if ($c)
            <div class="rounded-xl bg-gradient-to-br from-primary via-primary-container to-surface-tint text-on-primary p-space-lg shadow-xl space-y-space-md">
                <div class="flex items-center justify-between">
                    <span class="font-label-md text-label-md uppercase tracking-wider text-primary-fixed">{{ __('Tou') }} {{ $c->cycle_number }} {{ __('sou') }} {{ $cyclesTotal }}</span>
                    @if ($late)<span class="badge bg-secondary-container text-on-secondary-container">{{ __('An reta') }}</span>@endif
                </div>
                <div class="flex items-center gap-3 bg-white/10 rounded-xl p-space-sm">
                    <x-avatar :name="$c->beneficiary?->name" tone="bg-white text-primary-container" />
                    <div class="min-w-0">
                        <p class="font-body-sm text-body-sm text-primary-fixed">{{ __('Moun kap ranmase') }}</p>
                        <p class="font-title-md text-title-md truncate">{{ $c->beneficiary?->name ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5 tabular-nums">
                    <span class="font-currency-display text-currency-display">{{ number_format($tontine->potAmount(), 0, '.', ',') }}</span>
                    <span class="font-title-lg text-title-lg text-tertiary-fixed">{{ $cur }}</span>
                </div>
                <div class="space-y-1">
                    <div class="w-full h-3 bg-white/20 rounded-full overflow-hidden">
                        <div class="h-3 rounded-full bg-emerald-400" style="width: {{ $percent }}%"></div>
                    </div>
                    <div class="flex justify-between font-body-sm text-body-sm tabular-nums">
                        <span>{{ number_format($collected, 0, '.', ',') }} {{ $cur }} {{ __('ranmase') }}</span>
                        <span>{{ $paid }} {{ __('sou') }} {{ $total }} {{ __('peye') }} ({{ $percent }}%)</span>
                    </div>
                    <p class="font-body-sm text-body-sm text-primary-fixed">{{ __('Dat limit') }} : {{ $c->deadline->format('d/m/Y') }}</p>
                </div>
            </div>
        @elseif ($tontine->status === 'completed')
            <div class="bg-tertiary-fixed/40 text-on-tertiary-fixed-variant p-space-lg rounded-xl text-center space-y-space-sm">
                <p class="font-title-md text-title-md">🎉 {{ __('Kòl') }} {{ $tontine->current_round }} {{ __('fini!') }}</p>
                <a href="{{ route('tontines.cycles.index', $tontine) }}" class="btn-primary w-full">
                    <span class="material-symbols-outlined">autorenew</span> {{ __('Relanse yon nouvo kòl') }}
                </a>
            </div>
        @else
            <div class="card space-y-space-sm">
                <h3 class="font-title-md text-title-md">{{ __('Pou kòmanse') }}</h3>
                <p class="flex items-center gap-2 text-on-surface-variant"><span class="material-symbols-outlined text-[20px] {{ $tontine->members->count() >= 2 ? 'text-emerald-500' : '' }}">{{ $tontine->members->count() >= 2 ? 'check_circle' : 'radio_button_unchecked' }}</span> 1. {{ __('Ajoute omwen 2 manm') }}</p>
                <p class="flex items-center gap-2 text-on-surface-variant"><span class="material-symbols-outlined text-[20px]">radio_button_unchecked</span> 2. {{ __('Fikse lòd benefisyè yo') }}</p>
                <p class="flex items-center gap-2 text-on-surface-variant"><span class="material-symbols-outlined text-[20px]">radio_button_unchecked</span> 3. {{ __('Jenere tou yo') }}</p>
            </div>
        @endif

        {{-- Accès rapides --}}
        <div class="grid grid-cols-2 gap-space-sm">
            @php
                $links = [
                    ['tontines.cycles.index', 'event_repeat', __('Kalandriye tou yo'), true],
                    ['tontines.members.index', 'group', __('Manm yo') . ' (' . $tontine->members->count() . ')', true],
                    ['tontines.contributions.index', 'receipt_long', __('Peman yo'), $tontine->status !== 'draft'],
                    ['tontines.payouts.index', 'account_balance', __('Vèsman yo'), $tontine->status !== 'draft'],
                ];
            @endphp
            @foreach ($links as [$r, $icon, $label, $enabled])
                @if ($enabled)
                    <a href="{{ route($r, $tontine) }}" class="card flex flex-col items-center gap-1 py-space-md text-center active:scale-95 transition">
                        <span class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center"><span class="material-symbols-outlined text-primary-container">{{ $icon }}</span></span>
                        <span class="font-label-md text-label-md">{{ $label }}</span>
                    </a>
                @else
                    <div class="card flex flex-col items-center gap-1 py-space-md text-center opacity-40">
                        <span class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center"><span class="material-symbols-outlined">{{ $icon }}</span></span>
                        <span class="font-label-md text-label-md">{{ $label }}</span>
                    </div>
                @endif
            @endforeach
        </div>

        @if ($c)
            <a href="{{ route('tontines.contributions.index', $tontine) }}" class="btn-primary w-full">
                <span class="material-symbols-outlined">add_circle</span> {{ __('Anrejistre yon peman') }}
            </a>
        @endif

        @if ($tontine->cycles()->exists())
            <a href="{{ route('tontines.reports.index', $tontine) }}" class="btn-secondary w-full">
                <span class="material-symbols-outlined">description</span> {{ __('Rapò yo') }}
            </a>
        @endif

        <form method="POST" action="{{ route('tontines.destroy', $tontine) }}" class="text-center pt-space-md"
              onsubmit="return confirm('{{ __('Ou sèten ou vle efase tontine sa a?') }}')">
            @csrf @method('DELETE')
            <button class="font-label-md text-label-md text-secondary underline">{{ __('Efase tontine sa a') }}</button>
        </form>
    </div>
</x-app-layout>
