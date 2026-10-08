<x-app-layout>
    @php $cur = config('lajanansanm.currency'); @endphp
    <x-page-header :back="route('tontines.show', $tontine)" :title="__('Vèsman yo')" :subtitle="$tontine->name" />

    <div class="space-y-space-md">
        @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->has('payout'))<div class="alert-err">{{ $errors->first('payout') }}</div>@endif

        <div class="rounded-xl bg-gradient-to-br from-primary via-primary-container to-surface-tint text-on-primary p-space-lg shadow-xl">
            <p class="font-label-md text-label-md uppercase tracking-wider text-primary-fixed">{{ __('Total deja vèse') }}</p>
            <p class="flex items-baseline gap-1.5 tabular-nums mt-1"><span class="font-currency-display text-currency-display">{{ number_format($totalPaidOut, 0, '.', ',') }}</span><span class="font-title-lg text-title-lg text-tertiary-fixed">{{ $cur }}</span></p>
            @if ($totalFees > 0)<p class="font-body-sm text-body-sm text-primary-fixed mt-1">{{ __('Kòmisyon òganizatè') }} : {{ number_format($totalFees, 0, '.', ',') }} {{ $cur }}</p>@endif
        </div>

        @if ($cycles->isEmpty())
            <x-empty-state icon="account_balance" :title="__('Pa gen vèsman ankò.')" :text="__('Jenere tou yo anvan ou vèse lajan.')"
                :href="route('tontines.cycles.index', $tontine)" :action="__('Kalandriye tou yo')" />
        @endif

        @foreach ($cycles as $cycle)
            @php
                $collected = (float) ($cycle->contributions_sum_amount ?? 0);
                $paidCount = $cycle->contributions_count;
                $missing = max(0, $activeMembers - $paidCount);
                $fee = $tontine->feeFor($collected);
                $net = $collected - $fee;
            @endphp
            <div class="card space-y-space-sm {{ $cycle->status === 'open' ? 'ring-2 ring-primary-container' : '' }}">
                <div class="flex items-center gap-3">
                    <x-avatar :name="$cycle->beneficiary?->name" :rank="$cycle->cycle_number" />
                    <div class="flex-1 min-w-0">
                        <p class="font-title-md text-title-md truncate">{{ $cycle->beneficiary?->name ?? '—' }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">{{ $paidCount }}/{{ $activeMembers }} {{ __('peye') }} · {{ number_format($collected, 0, '.', ',') }} {{ $cur }}</p>
                    </div>
                    @if ($cycle->payout)<span class="badge-paid">✓ {{ __('Vèse') }}</span>
                    @elseif ($cycle->status === 'open')<span class="badge-wait">{{ __('Ap tann') }}</span>
                    @else<span class="badge-muted">{{ __('Ap vini') }}</span>@endif
                </div>

                @if ($cycle->payout)
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ __('Benefisyè a resevwa') }} <strong class="text-on-surface">{{ number_format($cycle->payout->netAmount(), 0, '.', ',') }} {{ $cur }}</strong>@if ((float) $cycle->payout->fee_amount > 0) ({{ __('kòmisyon') }} {{ number_format($cycle->payout->fee_amount, 0, '.', ',') }} {{ $cur }})@endif
                        {{ __('nan dat') }} {{ $cycle->payout->paid_at->format('d/m/Y') }}.
                    </p>
                @elseif ($cycle->status === 'open')
                    @if ($paidCount === 0)
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Pa gen okenn peman pou tou sa a.') }}</p>
                    @else
                        <form method="POST" action="{{ route('tontines.cycles.payout.store', [$tontine, $cycle]) }}" class="space-y-space-sm"
                              onsubmit="return confirm('{{ __('Vèse lajan an bay benefisyè a epi fèmen tou sa a? Sa pa ka anile.') }}')">
                            @csrf
                            @if ($missing > 0)
                                <label class="flex items-start gap-2 bg-amber-50 text-amber-800 p-space-sm rounded-xl font-body-sm text-body-sm">
                                    <input type="checkbox" name="confirm_incomplete" value="1" class="mt-0.5 rounded border-slate-300 text-primary-container">
                                    <span>{{ $missing }} {{ __('manm poko peye.') }} {{ __('Mwen konfime m vle vèse') }} <strong>{{ number_format($net, 0, '.', ',') }} {{ $cur }}</strong> {{ __('sèlman.') }}</span>
                                </label>
                            @endif
                            @if ($fee > 0)
                                <div class="bg-surface-container-low rounded-xl p-space-sm font-body-sm text-body-sm tabular-nums space-y-0.5">
                                    <div class="flex justify-between"><span>{{ __('Ranmase') }}</span><span>{{ number_format($collected, 0, '.', ',') }} {{ $cur }}</span></div>
                                    <div class="flex justify-between text-secondary"><span>{{ __('Kòmisyon òganizatè') }} ({{ (float) $tontine->organizer_fee_percent }}%)</span><span>− {{ number_format($fee, 0, '.', ',') }} {{ $cur }}</span></div>
                                    <div class="flex justify-between font-bold border-t border-slate-200 pt-0.5"><span>{{ __('Benefisyè a resevwa') }}</span><span>{{ number_format($net, 0, '.', ',') }} {{ $cur }}</span></div>
                                </div>
                            @endif
                            <button class="btn-success w-full"><span class="material-symbols-outlined">payments</span> {{ __('Vèse') }} {{ number_format($net, 0, '.', ',') }} {{ $cur }}</button>
                        </form>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>
