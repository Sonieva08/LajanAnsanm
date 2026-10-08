<x-app-layout>
    @php $cur = config('lajanansanm.currency'); $csvOk = auth()->user()?->is_owner || \App\Support\Entitlements::for(auth()->user())->has('csv_export'); @endphp
    <x-page-header :back="route('tontines.show', $tontine)" :title="__('Rapò yo')" :subtitle="$tontine->name" />

    <div class="space-y-space-lg">
        @forelse ($rounds as $round => $cycles)
            @php
                $collected = (float) $cycles->sum('contributions_sum_amount');
                $isCurrent = (int) $round === (int) $tontine->current_round;
            @endphp
            <div class="space-y-space-sm">
                <div class="card space-y-space-sm {{ $isCurrent ? 'ring-2 ring-primary-container' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-title-lg text-title-lg">{{ __('Kòl') }} {{ $round }}
                                @if ($isCurrent)<span class="badge-info ml-1">{{ __('An kou') }}</span>@endif
                            </h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">
                                {{ $cycles->first()->deadline->format('d/m/Y') }} → {{ $cycles->last()->deadline->format('d/m/Y') }}
                                · {{ $cycles->count() }} {{ __('tou') }}
                            </p>
                        </div>
                        <span class="badge-paid tabular-nums">{{ number_format($collected, 0, '.', ',') }} {{ $cur }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('tontines.reports.round', [$tontine, $round]) }}" class="btn-primary btn-sm">
                            <span class="material-symbols-outlined text-[18px]">description</span> {{ __('Rapò kòl la') }}
                        </a>
                        <a href="{{ ($csvOk ? route('tontines.reports.round', [$tontine, $round, 'format' => 'csv']) : route('plans')) }}" class="btn-secondary btn-sm">
                            <span class="material-symbols-outlined text-[18px]">download</span> CSV
                        </a>
                    </div>
                </div>

                <div class="card !p-0 divide-y divide-slate-100">
                    @foreach ($cycles as $c)
                        <div class="flex items-center gap-3 p-space-sm">
                            <span class="w-9 h-9 shrink-0 rounded-full bg-primary-fixed text-primary-container flex items-center justify-center font-title-md text-title-md">{{ $c->cycle_number }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="font-title-md text-title-md truncate">{{ $c->beneficiary?->name ?? '—' }}</p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">
                                    {{ $c->deadline->format('d/m/Y') }} · {{ number_format((float) ($c->contributions_sum_amount ?? 0), 0, '.', ',') }} {{ $cur }}
                                </p>
                            </div>
                            <a href="{{ route('tontines.reports.tour', [$tontine, $c]) }}" class="btn-secondary btn-sm">{{ __('Rapò') }}</a>
                            <a href="{{ ($csvOk ? route('tontines.reports.tour', [$tontine, $c, 'format' => 'csv']) : route('plans')) }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-container" title="CSV">
                                <span class="material-symbols-outlined text-[20px]">download</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <x-empty-state art="reports" :title="__('Poko gen rapò disponib')" :text="__('Rapò yo disponib depi ou jenere kalandriye a.')"
                :href="route('tontines.cycles.index', $tontine)" :action="__('Kalandriye tou yo')" />
        @endforelse
    </div>
</x-app-layout>
