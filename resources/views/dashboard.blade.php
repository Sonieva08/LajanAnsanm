<x-app-layout>
    @php
        $initialsOf = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
            ->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
        $cur = config('lajanansanm.currency');
        $firstActive = $tontines->firstWhere('status', 'active');
    @endphp

    <div class="space-y-space-lg">

        {{-- Salutation --}}
        <div>
            <span class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px] text-tertiary-fixed-dim" style="font-variation-settings:'FILL' 1">calendar_today</span>
                {{ now()->locale(app()->getLocale())->translatedFormat('l j F Y') }}
            </span>
            <h1 class="font-headline-lg-mobile text-headline-lg-mobile mt-0.5">
                {{ __('Bonjou') }}, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }} 👋
            </h1>
        </div>

        @if (session('status'))
            <div class="bg-tertiary-fixed/40 text-on-tertiary-fixed-variant p-space-md rounded-xl">{{ session('status') }}</div>
        @endif

        {{-- Carte principale --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary via-primary-container to-surface-tint text-on-primary p-space-lg shadow-xl">
            <span class="material-symbols-outlined absolute top-3 right-3 text-[80px] opacity-15 pointer-events-none">savings</span>

            <div class="relative z-10 space-y-space-md">
                <div>
                    <span class="font-label-md text-label-md text-primary-fixed uppercase tracking-wider block">{{ __('Total ranmase nan tout tontin') }}</span>
                    <div class="flex items-baseline gap-1.5 mt-1 tabular-nums">
                        <span class="font-currency-display text-currency-display">{{ number_format($stats['collected'], 0, '.', ',') }}</span>
                        <span class="font-title-lg text-title-lg text-tertiary-fixed">{{ $cur }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-space-xs">
                    <div class="flex flex-col items-center p-2 rounded-lg bg-white/10">
                        <span class="font-label-sm text-label-sm text-primary-fixed">{{ __('Tontin aktif') }}</span>
                        <span class="font-title-lg text-title-lg mt-0.5">{{ $stats['active_tontines'] }}</span>
                    </div>
                    <div class="flex flex-col items-center p-2 rounded-lg bg-white/10">
                        <span class="font-label-sm text-label-sm text-primary-fixed">{{ __('Manm') }}</span>
                        <span class="font-title-lg text-title-lg mt-0.5">{{ $stats['members'] }}</span>
                    </div>
                    <div class="flex flex-col items-center p-2 rounded-lg {{ $stats['late'] > 0 ? 'bg-secondary-container text-on-secondary-container' : 'bg-white/10' }}">
                        <span class="font-label-sm text-label-sm flex items-center gap-0.5 {{ $stats['late'] > 0 ? '' : 'text-primary-fixed' }}">
                            @if ($stats['late'] > 0)<span class="material-symbols-outlined text-[13px]">warning</span>@endif
                            {{ __('An reta') }}
                        </span>
                        <span class="font-title-lg text-title-lg mt-0.5">{{ $stats['late'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions rapides --}}
        <div class="space-y-space-xs">
            <span class="font-label-md text-label-md text-on-surface-variant font-bold uppercase tracking-wider">{{ __('Aksyon rapid') }}</span>
            <div class="grid grid-cols-3 gap-space-sm pt-1">
                <a href="{{ route('tontines.create') }}"
                   class="flex flex-col items-center justify-center rounded-xl bg-primary-container text-on-primary shadow-md active:scale-95 transition h-[88px]">
                    <span class="material-symbols-outlined text-[24px] mb-1">add_circle</span>
                    <span class="font-label-sm text-label-sm">{{ __('Nouvo tontin') }}</span>
                </a>

                <a href="{{ $firstActive ? route('tontines.contributions.index', $firstActive) : route('tontines.index') }}"
                   class="flex flex-col items-center justify-center rounded-xl bg-surface-container-lowest shadow-sm active:scale-95 transition h-[88px]">
                    <span class="material-symbols-outlined text-[24px] mb-1 text-primary-container">receipt_long</span>
                    <span class="font-label-sm text-label-sm">{{ __('Anrejistre') }}</span>
                </a>

                <a href="{{ $firstActive ? route('tontines.payouts.index', $firstActive) : route('tontines.index') }}"
                   class="flex flex-col items-center justify-center rounded-xl bg-surface-container-lowest shadow-sm active:scale-95 transition h-[88px]">
                    <span class="material-symbols-outlined text-[24px] mb-1 text-primary-container">account_balance</span>
                    <span class="font-label-sm text-label-sm">{{ __('Vèsman') }}</span>
                </a>
            </div>
        </div>

        {{-- Alertes de retard --}}
        @foreach ($alerts as $alert)
            <div class="bg-error-container/60 border border-red-200 p-space-md rounded-xl space-y-space-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-title-md text-title-md text-on-error-container flex items-center gap-1">
                            <span class="material-symbols-outlined text-[20px]">warning</span>
                            {{ $alert['tontine']->name }} · {{ __('Tou') }} {{ $alert['cycle']->cycle_number }}
                        </p>
                        <p class="font-body-sm text-body-sm text-on-error-container">
                            {{ __('Dat limit') }} : {{ $alert['cycle']->deadline->format('d/m/Y') }}
                        </p>
                    </div>
                    <a href="{{ route('tontines.contributions.index', $alert['tontine']) }}"
                       class="shrink-0 px-3 py-2 bg-secondary text-on-secondary rounded-xl font-label-sm text-label-sm">{{ __('Wè peman yo') }}</a>
                </div>

                @if ($alert['unpaid']->isNotEmpty())
                    <div class="flex flex-wrap gap-1">
                        @foreach ($alert['unpaid'] as $m)
                            <span class="bg-white px-2.5 py-0.5 rounded-full font-body-sm text-body-sm">
                                {{ $m->name }}@if ($m->phone) · {{ $m->phone }}@endif
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Liste des tontines --}}
        <div class="flex items-center gap-1.5 pt-space-xs">
            <h2 class="font-headline-md text-headline-md">{{ __('Tontin mwen yo') }}</h2>
            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-surface-container-highest text-primary font-label-sm text-label-sm">{{ $tontines->count() }}</span>
        </div>

        <div class="space-y-space-md">
            @forelse ($tontines as $tontine)
                @php
                    $cycle = $tontine->cycles->first();
                    $total = $tontine->active_members_count;
                    $paid = $cycle?->contributions_count ?? 0;
                    $percent = $total > 0 ? (int) round($paid / $total * 100) : 0;
                    $collected = (float) ($cycle?->contributions_sum_amount ?? 0);
                    $late = $cycle?->isLate() ?? false;
                    $pot = (float) $tontine->amount_per_period * $total;
                @endphp

                <div class="bg-surface-container-lowest rounded-xl p-space-md shadow-sm space-y-space-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="bg-surface-container-highest text-primary-container font-label-sm text-label-sm px-2.5 py-0.5 rounded-full">
                                    {{ $tontine->period_type === 'weekly' ? __('Chak semèn') : __('Chak mwa') }}
                                </span>
                                @if ($cycle)
                                    <span class="bg-surface-container text-on-surface-variant font-label-sm text-label-sm px-2.5 py-0.5 rounded-full">
                                        {{ __('Tou') }} {{ $cycle->cycle_number }} {{ __('sou') }} {{ $tontine->cycles_count }}
                                    </span>
                                @elseif ($tontine->status === 'draft')
                                    <span class="bg-surface-container text-on-surface-variant font-label-sm text-label-sm px-2.5 py-0.5 rounded-full">{{ __('Poko kòmanse') }}</span>
                                @elseif ($tontine->status === 'completed')
                                    <span class="bg-tertiary-fixed/50 text-on-tertiary-fixed-variant font-label-sm text-label-sm px-2.5 py-0.5 rounded-full">{{ __('Fini') }}</span>
                                @endif
                            </div>
                            <h3 class="font-title-lg text-title-lg mt-1.5 truncate">{{ $tontine->name }}</h3>
                        </div>
                        <div class="text-right shrink-0 tabular-nums">
                            <span class="font-title-md text-title-md text-primary-container block">{{ number_format($tontine->amount_per_period, 0, '.', ',') }} {{ $cur }}</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant normal-case">{{ __('pa moun') }}</span>
                        </div>
                    </div>

                    @if ($cycle)
                        <div class="flex items-center justify-between bg-surface-container-low px-space-sm py-1.5 rounded-lg tabular-nums">
                            <span class="font-label-md text-label-md text-on-surface-variant">{{ __('Valè po a') }}</span>
                            <span class="font-title-md text-title-md">{{ number_format($pot, 0, '.', ',') }} {{ $cur }}</span>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center justify-between font-body-sm text-body-sm">
                                <span class="flex items-center gap-1 {{ $late ? 'text-secondary' : 'text-on-surface-variant' }}">
                                    <span class="material-symbols-outlined text-[16px]">{{ $late ? 'schedule' : 'check_circle' }}</span>
                                    {{ $paid }} {{ __('sou') }} {{ $total }} {{ __('manm peye') }}
                                </span>
                                @if ($late)
                                    <span class="bg-red-100 text-red-700 font-label-sm text-label-sm px-2 py-0.5 rounded-full">{{ __('An reta') }}</span>
                                @else
                                    <span class="font-label-md text-label-md text-emerald-600">{{ $percent }}%</span>
                                @endif
                            </div>
                            <div class="w-full h-3 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-3 rounded-full {{ $late ? 'bg-secondary-container' : 'bg-emerald-500' }}" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 pt-1">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-11 h-11 shrink-0 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-label-md text-label-md">
                                    {{ $initialsOf($cycle->beneficiary?->name) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Moun kap ranmase') }}</p>
                                    <p class="font-title-md text-title-md truncate">{{ $cycle->beneficiary?->name ?? '—' }}</p>
                                    <p class="font-body-sm text-body-sm flex items-center gap-1 {{ $late ? 'text-secondary' : 'text-emerald-600' }}">
                                        <span class="material-symbols-outlined text-[14px]">event</span>
                                        {{ $cycle->deadline->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('tontines.show', $tontine) }}"
                               class="shrink-0 min-h-[44px] px-5 inline-flex items-center rounded-xl bg-primary-container text-on-primary font-label-lg text-label-lg active:scale-95 transition">
                                {{ __('Jere') }}
                            </a>
                        </div>
                    @else
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ $tontine->status === 'completed'
                                ? '🎉 ' . __('Tout tou yo fini.')
                                : __('Ajoute manm yo, answit jenere tou yo pou kòmanse.') }}
                        </p>
                        <a href="{{ route('tontines.show', $tontine) }}"
                           class="min-h-[44px] px-5 inline-flex items-center rounded-xl bg-primary-container text-on-primary font-label-lg text-label-lg">
                            {{ __('Jere') }}
                        </a>
                    @endif
                </div>
            @empty
                <x-empty-state art="tontine" :title="__('Ou pa gen tontin ankò')" :text="__('Kòmanse premye tontin kominotè w la kounye a pou w jere kotizasyon ak tiraj manm yo an sekirite.')"
                    :href="route('tontines.create')" :action="__('Kreye premye tontin ou')" />
            @endforelse
        </div>
    </div>

    {{-- Bouton flottant --}}
    <a href="{{ route('tontines.create') }}"
       class="fixed right-gutter z-30 inline-flex items-center gap-2 px-6 min-h-[52px] rounded-full bg-secondary text-on-secondary shadow-xl font-label-lg text-label-lg active:scale-95 transition"
       style="bottom: calc(5rem + env(safe-area-inset-bottom, 0px))">
        <span class="material-symbols-outlined">add</span> {{ __('Kreye yon tontine') }}
    </a>
</x-app-layout>
