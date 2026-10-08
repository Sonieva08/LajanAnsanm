@php
    $cur = config('lajanansanm.currency');
    $fmt = fn ($n) => number_format((float) $n, 0, '.', ',') . ' ' . $cur;
    $st = ['pending' => __('Ap vini'), 'open' => __('Ouvè'), 'closed' => __('Fèmen')];
    $first = $cycles->first()->deadline; $last = $cycles->last()->deadline;
@endphp

<x-report-layout :title="__('Rapò') . ' · ' . $tontine->name . ' · ' . __('Kòl') . ' ' . $round"
                 :back="route('tontines.reports.index', $tontine)"
                 :csv="request()->fullUrlWithQuery(['format' => 'csv'])">

    <h1 class="text-2xl font-bold">{{ $tontine->name }}</h1>
    <p class="text-slate-600 mb-5">{{ __('Kòl') }} {{ $round }} · {{ $first->format('d/m/Y') }} → {{ $last->format('d/m/Y') }}
        · {{ $totals['closed'] }}/{{ $totals['count'] }} {{ __('tou fèmen') }}</p>

    <div class="grid grid-cols-3 gap-3 mb-6 no-break">
        <div class="border border-slate-200 rounded-lg p-3"><p class="text-xs text-slate-500">{{ __('Total ranmase') }}</p><p class="font-semibold tabular-nums text-green-700">{{ $fmt($totals['collected']) }}</p></div>
        <div class="border border-slate-200 rounded-lg p-3"><p class="text-xs text-slate-500">{{ __('Total vèse') }}</p><p class="font-semibold tabular-nums">{{ $fmt($totals['paid_out']) }}</p></div>
        <div class="border border-slate-200 rounded-lg p-3"><p class="text-xs text-slate-500">{{ __('Total dwe') }}</p><p class="font-semibold tabular-nums {{ $totals['owed'] > 0 ? 'text-red-700' : '' }}">{{ $fmt($totals['owed']) }}</p></div>
    </div>

    @if ($totals['fees'] > 0)
        <p class="mb-4 text-sm"><strong>{{ __('Total kòmisyon') }} :</strong> <span class="tabular-nums">{{ $fmt($totals['fees']) }}</span>
            <span class="text-slate-500">({{ __('deja soustrè nan montan vèse yo') }})</span></p>
    @endif

    <h2 class="font-bold mb-2">{{ __('Detay pa tou') }}</h2>
    <table class="w-full text-left mb-6">
        <thead>
            <tr class="border-b-2 border-slate-300 text-xs text-slate-500 uppercase">
                <th class="py-2 pr-2">{{ __('Tou') }}</th><th class="pr-2">{{ __('Benefisyè') }}</th><th class="pr-2">{{ __('Dat limit') }}</th>
                <th class="pr-2">{{ __('Peye') }}</th><th class="pr-2 text-right">{{ __('Ranmase') }}</th><th class="pr-2 text-right">{{ __('Vèse') }}</th><th>{{ __('Estati') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cycles as $c)
                <tr class="border-b border-slate-200">
                    <td class="py-2 pr-2 tabular-nums">{{ $c->cycle_number }}</td>
                    <td class="pr-2 font-medium">{{ $c->beneficiary?->name ?? '—' }}</td>
                    <td class="pr-2 tabular-nums">{{ $c->deadline->format('d/m/Y') }}</td>
                    <td class="pr-2 tabular-nums">{{ $c->contributions_count }}</td>
                    <td class="pr-2 text-right tabular-nums">{{ number_format((float) ($c->contributions_sum_amount ?? 0), 0, '.', ',') }}</td>
                    <td class="pr-2 text-right tabular-nums">{{ $c->payout ? number_format($c->payout->netAmount(), 0, '.', ',') : '—' }}</td>
                    <td>{{ $st[$c->status] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2 class="font-bold mb-2">{{ __('Detay pa manm') }}</h2>
    <table class="w-full text-left">
        <thead>
            <tr class="border-b-2 border-slate-300 text-xs text-slate-500 uppercase">
                <th class="py-2 pr-2">#</th><th class="pr-2">{{ __('Non') }}</th><th class="pr-2">{{ __('Tou peye') }}</th>
                <th class="pr-2 text-right">{{ __('Total peye') }}</th><th class="pr-2">{{ __('Tou manke') }}</th>
                <th class="pr-2 text-right">{{ __('Lajan dwe') }}</th><th class="text-right">{{ __('Lajan resevwa') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($memberRows as $r)
                <tr class="border-b border-slate-200">
                    <td class="py-2 pr-2 tabular-nums">{{ $r['member']->position ?? '—' }}</td>
                    <td class="pr-2 font-medium">{{ $r['member']->name }}@unless ($r['member']->is_active) <span class="text-xs font-normal text-slate-500">({{ __('retire') }})</span>@endunless</td>
                    <td class="pr-2 tabular-nums">{{ $r['paid_count'] }}</td>
                    <td class="pr-2 text-right tabular-nums">{{ number_format($r['paid_sum'], 0, '.', ',') }}</td>
                    <td class="pr-2 tabular-nums {{ $r['missed'] > 0 ? 'text-red-700 font-semibold' : '' }}">{{ $r['missed'] }}</td>
                    <td class="pr-2 text-right tabular-nums {{ $r['owed'] > 0 ? 'text-red-700 font-semibold' : '' }}">{{ number_format($r['owed'], 0, '.', ',') }}</td>
                    <td class="text-right tabular-nums">{{ $r['received'] > 0 ? number_format($r['received'], 0, '.', ',') : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="mt-4 text-xs text-slate-500">{{ __('Lajan dwe = tou fèmen oswa an reta ki pa peye × kotizasyon an.') }}</p>
</x-report-layout>
