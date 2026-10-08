<x-app-layout>
    @php
        $cur = config('lajanansanm.currency');
        $stateBadge = match ($ent->state) {
            'active' => ['badge-paid', __('Aktif')],
            'grace' => ['badge-wait', __('Peryòd gras')],
            'expired' => ['badge-late', __('Ekspire')],
            'owner' => ['badge-info', __('Pwopriyetè')],
            default => ['badge-muted', __('Gratis')],
        };
        $statusBadge = ['pending' => ['badge-wait', __('Ap tann')], 'approved' => ['badge-paid', __('Konfime')], 'rejected' => ['badge-late', __('Rejte')]];
        $periodLabel = ['monthly' => __('Chak mwa'), 'quarterly' => __('3 mwa'), 'yearly' => __('Chak ane')];
        $tontines = $ent->activeTontines();
    @endphp

    <x-page-header :title="__('Abònman mwen')" :subtitle="$ent->plan->name" />

    <div class="space-y-space-lg">
        @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif

        <div class="rounded-xl bg-gradient-to-br from-primary via-primary-container to-surface-tint text-on-primary p-space-lg shadow-xl space-y-space-sm">
            <div class="flex items-center justify-between">
                <span class="font-label-md text-label-md uppercase tracking-wider text-primary-fixed">{{ __('Plan ou an') }}</span>
                <span class="{{ $stateBadge[0] }}">{{ $stateBadge[1] }}</span>
            </div>
            <p class="font-headline-lg text-headline-lg">{{ $ent->plan->name }}</p>
            @if ($ent->state === 'active')
                <p class="text-primary-fixed">{{ __('Ekspire le') }} {{ $ent->subscription->ends_at->format('d/m/Y') }} · {{ $ent->daysLeft() }} {{ __('jou rete') }}</p>
            @elseif ($ent->state === 'grace')
                <p class="text-primary-fixed">{{ __('Abònman an fini le') }} {{ $ent->subscription->ends_at->format('d/m/Y') }}. {{ __('Ou gen') }} {{ $ent->graceDaysLeft() }} {{ __('jou pou renouvle.') }}</p>
            @elseif ($ent->state === 'expired')
                <p class="text-primary-fixed">{{ __('Abònman an fini le') }} {{ $ent->subscription->ends_at->format('d/m/Y') }}.</p>
            @endif
            <a href="{{ route('plans') }}" class="btn bg-white text-primary-container w-full mt-2">{{ $ent->isPaid() ? __('Renouvle oswa chanje plan') : __('Wè plan yo') }}</a>
        </div>

        <div class="card !p-space-lg space-y-space-md">
            <h2 class="font-title-lg text-title-lg">{{ __('Itilizasyon') }}</h2>
            <div>
                <div class="flex justify-between font-label-md text-label-md mb-1"><span>{{ __('Tontin aktif') }}</span><span class="tabular-nums">{{ $tontines }} / {{ $ent->maxTontines() ?? '∞' }}</span></div>
                @php $pct = $ent->maxTontines() ? min(100, round($tontines / $ent->maxTontines() * 100)) : 0; @endphp
                <div class="h-2.5 bg-slate-200 rounded-full overflow-hidden"><div class="h-2.5 rounded-full {{ $pct >= 100 ? 'bg-secondary' : 'bg-primary-container' }}" style="width: {{ $pct }}%"></div></div>
            </div>
            <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Manm pa tontin') }} : {{ $ent->maxMembers() ?? '∞' }}</p>
            @if (($ro = $ent->readOnlyCount()) > 0)
                <p class="alert-err">{{ $ro }} {{ __('tontin an lekti sèlman paske yo depase limit plan ou an.') }}</p>
            @endif
        </div>

        @if ($pending)
            <div class="card !p-space-lg space-y-2 border-2 border-amber-300" data-pending-request>
                <span class="badge-wait">{{ __('Ap tann konfimasyon') }}</span>
                <p class="font-title-md text-title-md">{{ __('Nou resevwa deklarasyon peman ou an!') }}</p>
                <p class="font-body-md text-body-md text-on-surface-variant">{{ $pending->plan->name }} · {{ $periodLabel[$pending->billing_period] }} · {{ number_format($pending->amount, 0, '.', ',') }} {{ $cur }}</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Referans') }} : <strong>{{ $pending->reference_code }}</strong> · {{ __('Nou verifye l, sa ka pran jiska 24 èdtan.') }}</p>
            </div>
        @endif

        @if ($history->isNotEmpty())
            <div class="space-y-space-sm">
                <h2 class="font-title-lg text-title-lg">{{ __('Istwa demann yo') }}</h2>
                @foreach ($history as $r)
                    <div class="card !p-space-sm flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-title-md text-title-md truncate">{{ $r->plan->name }} · {{ $periodLabel[$r->billing_period] }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">{{ $r->created_at->format('d/m/Y') }} · {{ number_format($r->amount, 0, '.', ',') }} {{ $cur }} · {{ $r->reference_code }}</p>
                            @if ($r->status === 'rejected' && $r->review_note)<p class="font-body-sm text-body-sm text-secondary">{{ $r->review_note }}</p>@endif
                        </div>
                        <span class="{{ $statusBadge[$r->status][0] }}">{{ $statusBadge[$r->status][1] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
