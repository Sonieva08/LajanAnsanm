@php
    $cur = config('lajanansanm.currency');
    $fmt = fn ($n) => number_format((float) $n, 0, '.', ',') . ' ' . $cur;
    $labels = ['paid' => __('Peye'), 'missed' => __('Pa peye'), 'late' => __('An reta'), 'waiting' => __('Ap tann'), 'upcoming' => __('Ap vini')];
    $colors = ['paid' => 'text-green-700', 'missed' => 'text-red-700', 'late' => 'text-red-700', 'waiting' => 'text-amber-700', 'upcoming' => 'text-slate-500'];
    $methods = ['cash' => __('Lajan kach'), 'moncash' => 'MonCash', 'natcash' => 'NatCash', 'bank' => __('Bank')];
    $cycleStatus = ['pending' => __('Ap vini'), 'open' => __('Ouvè'), 'closed' => __('Fèmen')][$cycle->status];
@endphp

<x-report-layout :title="__('Rapò') . ' · ' . $tontine->name . ' · ' . __('Tou') . ' ' . $cycle->cycle_number"
                 :back="route('tontines.reports.index', $tontine)"
                 :csv="request()->fullUrlWithQuery(['format' => 'csv'])">

    <h1 class="text-2xl font-bold">{{ $tontine->name }}</h1>
    <p class="text-slate-600 mb-5">{{ __('Kòl') }} {{ $cycle->round }} · {{ __('Tou') }} {{ $cycle->cycle_number }} · {{ $cycleStatus }}</p>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6 no-break">
        <div class="border border-slate-200 rounded-lg p-3"><p class="text-xs text-slate-500">{{ __('Benefisyè') }}</p><p class="font-semibold">{{ $cycle->beneficiary?->name ?? '—' }}</p></div>
        <div class="border border-slate-200 rounded-lg p-3"><p class="text-xs text-slate-500">{{ __('Dat limit') }}</p><p class="font-semibold">{{ $cycle->deadline->format('d/m/Y') }}</p></div>
        <div class="border border-slate-200 rounded-lg p-3"><p class="text-xs text-slate-500">{{ __('Peye') }}</p><p class="font-semibold">{{ $paidCount }} / {{ $memberCount }}</p></div>
        <div class="border border-slate-200 rounded-lg p-3"><p class="text-xs text-slate-500">{{ __('Lajan atann') }}</p><p class="font-semibold tabular-nums">{{ $fmt($expected) }}</p></div>
        <div class="border border-slate-200 rounded-lg p-3"><p class="text-xs text-slate-500">{{ __('Ranmase') }}</p><p class="font-semibold tabular-nums text-green-700">{{ $fmt($collected) }}</p></div>
        <div class="border border-slate-200 rounded-lg p-3"><p class="text-xs text-slate-500">{{ __('Manke') }}</p><p class="font-semibold tabular-nums {{ $missing > 0 ? 'text-red-700' : '' }}">{{ $fmt($missing) }}</p></div>
    </div>

    <h2 class="font-bold mb-2">{{ __('Detay pa manm') }}</h2>
    <table class="w-full text-left">
        <thead>
            <tr class="border-b-2 border-slate-300 text-xs text-slate-500 uppercase">
                <th class="py-2 pr-2">#</th><th class="pr-2">{{ __('Non') }}</th><th class="pr-2">{{ __('Estati') }}</th>
                <th class="pr-2">{{ __('Metòd peman') }}</th><th class="pr-2">{{ __('Dat') }}</th><th class="text-right">{{ __('Kantite') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                @php $c = $r['contribution']; @endphp
                <tr class="border-b border-slate-200 align-top">
                    <td class="py-2 pr-2 tabular-nums">{{ $r['member']->position ?? '—' }}</td>
                    <td class="pr-2 font-medium">{{ $r['member']->name }}@if ($r['member']->id === $cycle->beneficiary_member_id) ★@endif
                        @if ($c?->note)<span class="block text-xs font-normal text-slate-500">{{ $c->note }}</span>@endif
                    </td>
                    <td class="pr-2 font-semibold {{ $colors[$r['status']] }}">{{ $labels[$r['status']] }}</td>
                    <td class="pr-2">{{ $c ? ($methods[$c->payment_method] ?? $c->payment_method) : '—' }}</td>
                    <td class="pr-2 tabular-nums">{{ $c?->paid_at->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-right tabular-nums">{{ $c ? number_format($c->amount, 0, '.', ',') : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-bold"><td colspan="5" class="pt-3 text-right pr-2">{{ __('Ranmase') }}</td><td class="pt-3 text-right tabular-nums">{{ $fmt($collected) }}</td></tr>
        </tfoot>
    </table>

    <div class="mt-6 border border-slate-200 rounded-lg p-4 no-break">
        <h2 class="font-bold mb-1">{{ __('Vèsman') }}</h2>
        @if ($payout)
            @if ((float) $payout->fee_amount > 0)
                <table class="w-full tabular-nums">
                    <tr><td class="py-0.5">{{ __('Ranmase') }}</td><td class="text-right">{{ $fmt($payout->amount) }}</td></tr>
                    <tr><td class="py-0.5">{{ __('Kòmisyon òganizatè') }} ({{ (float) $payout->fee_percent }}%)</td><td class="text-right text-red-700">− {{ $fmt($payout->fee_amount) }}</td></tr>
                    <tr class="font-bold border-t border-slate-300"><td class="py-1">{{ __('Benefisyè a resevwa') }}</td><td class="text-right">{{ $fmt($payout->netAmount()) }}</td></tr>
                </table>
                <p class="text-xs text-slate-500 mt-1">{{ __('nan dat') }} {{ $payout->paid_at->format('d/m/Y') }}</p>
            @else
                <p>{{ __('Benefisyè a resevwa') }} <strong class="tabular-nums">{{ $fmt($payout->netAmount()) }}</strong> {{ __('nan dat') }} {{ $payout->paid_at->format('d/m/Y') }}.</p>
            @endif
        @else
            <p class="text-slate-500">{{ __('Poko gen vèsman pou tou sa a.') }}</p>
        @endif
    </div>

    <p class="mt-4 text-xs text-slate-500">{{ __('Lajan atann kalkile ak kotizasyon aktyèl la ak manm ki konsène yo.') }}</p>
</x-report-layout>
