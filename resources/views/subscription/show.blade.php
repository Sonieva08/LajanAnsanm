<x-app-layout>
    @php
        $cur = config('lajanansanm.currency');
        $sub = $ent->subscription;
        $periodLabel = ['monthly' => __('Chak mwa'), 'quarterly' => __('3 mwa'), 'yearly' => __('Chak ane')];
        $periodShort = ['monthly' => __('mwa'), 'quarterly' => __('3 mwa'), 'yearly' => __('ane')];
        $state = match ($ent->state) {
            'active' => ['bg-tertiary-container text-tertiary-fixed', 'check_circle', __('AKTIF')],
            'grace' => ['bg-amber-100 text-amber-900', 'hourglass_bottom', __('PERYÒD GRAS')],
            'expired' => ['bg-error-container text-on-error-container', 'event_busy', __('EKSPIRE')],
            'owner' => ['bg-tertiary-container text-tertiary-fixed', 'shield_person', __('PWOPRIYETÈ')],
            default => ['bg-surface-container-high text-on-surface-variant', 'redeem', __('GRATIS')],
        };
        $reqBadge = [
            'pending' => ['bg-surface-container-lowest text-on-surface', __('Ap tann konfimasyon')],
            'approved' => ['bg-tertiary-container text-tertiary-fixed', __('Konfime')],
            'rejected' => ['bg-error-container text-on-error-container', __('Rejte')],
        ];
        $maxT = $ent->maxTontines(); $usedT = $ent->activeTontines();
        $pctT = $maxT ? min(100, (int) round($usedT / $maxT * 100)) : 0;
        $maxM = $ent->maxMembers();
        $pctM = $maxM ? min(100, (int) round($membersUsed / $maxM * 100)) : 0;
        $waDigits = preg_replace('/\D/', '', (string) config('lajanansanm.support_whatsapp'));
        $methodLabel = \App\Models\SubscriptionRequest::METHODS;
        $onlinePaymentNeedsReview = $latestOnlinePayment?->status === 'review';
    @endphp

    <x-page-header :title="__('Abònman mwen')" :subtitle="__('Jere plan ou, swiv limit yo ak istorik peman ou.')" />

    <div class="space-y-space-md">
        @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="alert-err">{{ session('error') }}</div>@endif

        {{-- Carte principale --}}
        <div class="relative overflow-hidden rounded-xl bg-primary-container text-on-primary p-space-md shadow-md" data-plan-hero>
            <div class="absolute -right-8 -top-8 w-44 h-44 rounded-full bg-surface-tint/20 blur-2xl pointer-events-none"></div>
            <div class="relative space-y-space-md">
                <div class="flex items-start justify-between gap-space-sm">
                    <div>
                        <span class="font-label-sm text-label-sm uppercase tracking-wider text-surface-variant/80">{{ __('Plan kouran') }}</span>
                        <h2 class="font-headline-lg-mobile text-headline-lg-mobile leading-tight mt-0.5">{{ $ent->plan->name }}</h2>
                    </div>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-label-sm text-label-sm shadow-sm {{ $state[0] }}">
                        <span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'FILL' 1;">{{ $state[1] }}</span> {{ $state[2] }}
                    </span>
                </div>

                @if ($sub && (float) $sub->amount > 0 && $sub->billing_period)
                    <p class="flex items-baseline gap-1.5 tabular-nums">
                        <span class="font-currency-display text-currency-display">{{ number_format((float) $sub->amount, 0, '.', ',') }} {{ $cur }}</span>
                        <span class="text-surface-variant">/ {{ $periodShort[$sub->billing_period] }}</span>
                    </p>
                @endif

                @if ($sub && in_array($ent->state, ['active', 'grace', 'expired']))
                    <div class="flex items-center justify-between rounded-lg bg-primary/60 px-space-md py-space-sm">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-primary-fixed-dim">event_upcoming</span>
                            <div>
                                <p class="font-body-sm text-body-sm text-surface-variant/90 leading-tight">{{ $ent->state === 'expired' ? __('Abònman an fini le') : __('Dat ekspirasyon') }}</p>
                                <p class="font-label-md text-label-md font-bold">{{ $sub->ends_at->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        @if ($ent->state === 'active')
                            <span class="px-2.5 py-0.5 rounded-full bg-surface-tint/40 font-label-sm text-label-sm">{{ $ent->daysLeft() }} {{ __('jou ki rete') }}</span>
                        @elseif ($ent->state === 'grace')
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-label-sm text-label-sm">{{ $ent->graceDaysLeft() }} {{ __('jou pou renouvle') }}</span>
                        @endif
                    </div>
                @endif

                <a href="{{ route('plans') }}" class="w-full min-h-[48px] px-space-md rounded-lg bg-surface-container-lowest text-primary-container font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 shadow-sm active:scale-[0.98] transition">
                    <span class="material-symbols-outlined text-[20px]">autorenew</span> {{ $ent->isPaid() || $ent->state === 'expired' ? __('Renouvle oswa chanje plan') : __('Wè plan yo') }}
                </a>
            </div>
        </div>

        @if ($latestOnlinePayment)
            <section class="card !p-space-md space-y-space-md {{ $onlinePaymentNeedsReview ? 'border border-amber-300' : 'border border-emerald-200' }}" data-online-payment-receipt>
                <div class="flex items-start gap-space-sm">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $onlinePaymentNeedsReview ? 'bg-amber-100 text-amber-800' : 'bg-tertiary-fixed text-on-tertiary-fixed' }}">
                        <span class="material-symbols-outlined text-[24px]" aria-hidden="true">{{ $onlinePaymentNeedsReview ? 'hourglass_top' : 'check_circle' }}</span>
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-title-lg text-title-lg">{{ $onlinePaymentNeedsReview ? __('Peman an ap verifye') : __('Peman konfime') }}</h2>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ $onlinePaymentNeedsReview ? __('Peman an resevwa men li bezwen verifikasyon anplis.') : __('Abònman an aktive apre konfimasyon MonCash.') }}
                        </p>
                    </div>
                </div>

                <dl class="space-y-2 rounded-lg bg-surface-container-low p-space-md">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Plan') }}</dt>
                        <dd class="text-right font-label-md text-label-md font-semibold">{{ $latestOnlinePayment->plan->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Metòd peman') }}</dt>
                        <dd class="text-right font-label-md text-label-md">{{ __('MonCash atravè MonCashConnect') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Montan peye') }}</dt>
                        <dd class="text-right font-label-md text-label-md font-bold tabular-nums">{{ number_format((float) $latestOnlinePayment->amount, 0, '.', ',') }} {{ $cur }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Referans peman') }}</dt>
                        <dd class="break-all text-right font-label-sm text-label-sm font-mono">{{ $latestOnlinePayment->reference_id }}</dd>
                    </div>
                    @if ($latestOnlinePayment->subscription?->ends_at && ! $onlinePaymentNeedsReview)
                        <div class="flex items-center justify-between gap-3 border-t border-surface-container-highest pt-2">
                            <dt class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Abònman aktive jiska') }}</dt>
                            <dd class="text-right font-label-md text-label-md font-semibold">{{ $latestOnlinePayment->subscription->ends_at->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($onlinePaymentNeedsReview)
                    <p class="font-body-sm text-on-surface-variant">{{ __('Pa fè yon lòt peman pou menm tranzaksyon an pandan n ap verifye li.') }}</p>
                @endif
            </section>
        @endif

        {{-- Usage --}}
        <div class="card !p-space-md space-y-space-md">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-surface-container text-primary-container flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">data_usage</span></span>
                <div><h3 class="font-title-md text-title-md">{{ __('Itilizasyon ak limit plan') }}</h3><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Limit aktyèl ki disponib nan kont ou') }}</p></div>
            </div>

            <div class="space-y-1.5 rounded-lg p-space-sm {{ $maxT && $pctT >= 100 ? 'bg-error-container/40' : '' }}">
                <div class="flex items-center justify-between font-label-md text-label-md">
                    <span class="flex items-center gap-1.5 font-semibold"><span class="material-symbols-outlined text-[16px] text-surface-tint">group_work</span> {{ __('Sòl aktif') }}</span>
                    <span class="font-bold tabular-nums">{{ $usedT }} / {{ $maxT ?? '∞' }} @if ($maxT)<span class="text-on-surface-variant font-normal">({{ $pctT }}%)</span>@endif</span>
                </div>
                <div class="h-3 w-full bg-surface-container rounded-full overflow-hidden p-0.5"><div class="h-full rounded-full {{ $pctT >= 100 ? 'bg-error' : 'bg-primary-container' }}" style="width: {{ $pctT }}%"></div></div>
                <p class="font-body-sm text-body-sm {{ $maxT && $pctT >= 100 ? 'text-on-error-container font-medium' : 'text-on-surface-variant' }}">
                    @if (! $maxT) {{ __('Ou ka kreye otan sòl ou vle.') }}
                    @elseif ($pctT >= 100) {{ __('Ou rive nan limit sòl aktif. Pou kreye plis, chwazi yon plan ki pi gwo.') }}
                    @else {{ __('Ou ka kreye') }} <strong class="text-on-surface">{{ $maxT - $usedT }} {{ __('sòl anplis') }}</strong> {{ __('sou plan sa a.') }}
                    @endif
                </p>
            </div>

            <div class="space-y-1.5 rounded-lg p-space-sm {{ $maxM && $pctM >= 100 ? 'bg-error-container/40' : '' }}">
                <div class="flex items-center justify-between font-label-md text-label-md">
                    <span class="flex items-center gap-1.5 font-semibold {{ $maxM && $pctM >= 100 ? 'text-error' : '' }}"><span class="material-symbols-outlined text-[16px] {{ $maxM && $pctM >= 100 ? 'text-error' : 'text-surface-tint' }}">groups</span> {{ __('Manm pa sòl (maks)') }}</span>
                    <span class="font-bold tabular-nums {{ $maxM && $pctM >= 100 ? 'text-error' : '' }}">{{ $membersUsed }} / {{ $maxM ?? '∞' }}</span>
                </div>
                <div class="h-3 w-full bg-surface-container rounded-full overflow-hidden p-0.5"><div class="h-full rounded-full {{ $pctM >= 100 ? 'bg-error' : 'bg-on-tertiary-container' }}" style="width: {{ $pctM }}%"></div></div>
                <p class="font-body-sm text-body-sm {{ $maxM && $pctM >= 100 ? 'text-on-error-container font-medium' : 'text-on-surface-variant' }}">
                    {{ $maxM ? ($pctM >= 100 ? __('Ou rive nan limit manm pa sòl. Pou ajoute plis manm, chwazi yon plan ki pi gwo.') : __('Sòl ki gen pi plis manm lan :n manm.', ['n' => $membersUsed])) : __('Pa gen limit manm sou plan sa a.') }}
                </p>
            </div>

            @if (($ro = $ent->readOnlyCount()) > 0)
                <p class="alert-err">{{ $ro }} {{ __('sòl la lekti sèlman paske yo depase limit plan ou an.') }}</p>
            @endif
        </div>

        {{-- Demande en cours --}}
        @if ($pending)
            <div class="rounded-xl bg-surface-container-high p-space-md shadow-sm space-y-space-sm" data-pending-request x-data="{ copied: false }">
                <div class="flex items-start gap-space-sm">
                    <span class="w-10 h-10 rounded-full bg-surface-variant flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-[24px]">pending_actions</span></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 flex-wrap">
                            <h4 class="font-title-md text-title-md font-bold">{{ __('Demann an kou') }}</h4>
                            <span class="px-2.5 py-0.5 rounded-full bg-surface-container-lowest font-label-sm text-label-sm font-semibold flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-surface-tint animate-pulse"></span> {{ __('Ap tann konfimasyon') }}</span>
                        </div>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ __('Nou resevwa deklarasyon peman ou an. Nou ap verifye li, sa ka pran jiska 24 èdtan.') }}</p>
                    </div>
                </div>
                <div class="rounded-lg bg-surface-container-lowest p-space-sm space-y-2 font-body-sm text-body-sm">
                    <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('Plan mande') }}</span><span class="font-semibold">{{ $pending->plan->name }} ({{ $periodLabel[$pending->billing_period] }})</span></div>
                    <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('Montan') }}</span><span class="font-bold tabular-nums">{{ number_format($pending->amount, 0, '.', ',') }} {{ $cur }}</span></div>
                    <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('Metòd') }}</span><span class="font-medium">{{ $methodLabel[$pending->payment_method] ?? $pending->payment_method }}</span></div>
                    @if ($pending->payer_phone)<div class="flex justify-between"><span class="text-on-surface-variant">{{ __('Nimewo kont ki te voye lajan an') }}</span><span class="font-medium tabular-nums">{{ $pending->payer_phone }}</span></div>@endif
                    <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('Nimewo/kont ki te resevwa lajan an') }}</span><span class="font-medium tabular-nums">{{ $pending->recipient_account ?: '—' }}</span></div>
                    @if ($pending->transaction_ref)
                        <div class="flex justify-between items-center"><span class="text-on-surface-variant">{{ __('Kòd tranzaksyon') }}</span>
                        <span class="flex items-center gap-1.5"><code class="font-mono bg-surface-container px-2 py-0.5 rounded font-bold">{{ $pending->transaction_ref }}</code>
                            <button type="button" class="p-1 rounded text-on-surface-variant" aria-label="{{ __('Kopye') }}" @click="navigator.clipboard && navigator.clipboard.writeText(@js($pending->transaction_ref)); copied = true; setTimeout(() => copied = false, 1800)">
                                <span class="material-symbols-outlined text-[16px]" x-text="copied ? 'done' : 'content_copy'">content_copy</span></button></span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('Referans nòt') }}</span><code class="font-mono font-bold">{{ $pending->reference_code }}</code></div>
                    <div class="flex justify-between text-on-surface-variant"><span>{{ __('Dat voye') }}</span><span>{{ $pending->created_at->format('d/m/Y H:i') }}</span></div>
                    @if ($pending->hasReceipt())
                        <a href="{{ route('subscription.requests.receipt', $pending) }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 font-label-md text-label-md text-primary-container underline">
                            <span class="material-symbols-outlined text-[18px]">receipt_long</span>{{ __('Gade resi peman ou te voye a') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Historique --}}
        <div class="card !p-space-md space-y-space-md">
            <div class="flex items-center justify-between">
                <h3 class="font-title-md text-title-md font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-primary-container">history</span> {{ __('Istwa demann abònman') }}</h3>
                <span class="font-label-sm text-label-sm bg-surface-container text-on-surface-variant px-2.5 py-0.5 rounded-full">{{ $history->count() }}</span>
            </div>

            @forelse ($history as $r)
                <div class="rounded-lg bg-surface-container-low p-space-sm space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-label-lg text-label-lg font-semibold">{{ $r->plan->name }} ({{ $periodLabel[$r->billing_period] }})</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $r->created_at->format('d/m/Y') }} · {{ __('Ref') }} : <code class="font-mono">{{ $r->reference_code }}</code></p>
                        </div>
                        <div class="space-y-1 font-body-sm text-body-sm text-on-surface-variant">
                            <p>{{ __('Kont ki te voye lajan an') }}: <span class="font-medium tabular-nums text-on-surface">{{ $r->payer_phone }}</span></p>
                            <p>{{ __('Kont ki te resevwa lajan an') }}: <span class="font-medium tabular-nums text-on-surface">{{ $r->recipient_account ?: '—' }}</span></p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="font-label-md text-label-md font-bold tabular-nums">{{ number_format($r->amount, 0, '.', ',') }} {{ $cur }}</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full font-label-sm text-label-sm font-bold {{ $reqBadge[$r->status][0] }}">{{ $reqBadge[$r->status][1] }}</span>
                        </div>
                    </div>
                    @if ($r->hasReceipt())
                        <a href="{{ route('subscription.requests.receipt', $r) }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 font-label-sm text-label-sm text-primary-container underline">
                            <span class="material-symbols-outlined text-[16px]">receipt_long</span>{{ __('Gade resi peman an') }}
                        </a>
                    @endif
                    @if ($r->status === 'rejected' && $r->review_note)
                        <div class="flex items-start gap-2 p-space-xs rounded bg-error-container/70 text-on-error-container">
                            <span class="material-symbols-outlined text-[18px] text-error shrink-0 mt-0.5">error_outline</span>
                            <p class="font-body-sm text-body-sm"><strong>{{ __('Rezon rejè') }} :</strong> {{ $r->review_note }}</p>
                        </div>
                    @endif
                </div>
            @empty
                <x-empty-state compact icon="receipt_long" :title="__('Poko gen demann')" :text="__('Demann abònman ou yo ap parèt isit la.')" />
            @endforelse
        </div>

        @if ($waDigits !== '')
            <div class="rounded-xl bg-surface-container p-space-md flex items-center justify-between gap-space-sm">
                <div class="flex items-center gap-space-sm min-w-0">
                    <span class="w-10 h-10 rounded-full bg-tertiary-fixed-dim/40 flex items-center justify-center shrink-0 text-tertiary"><span class="material-symbols-outlined">support_agent</span></span>
                    <div class="min-w-0"><p class="font-title-md text-title-md font-bold leading-tight">{{ __('Bezwen èd ak abònman w?') }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Kontakte sipò a sou WhatsApp.') }}</p></div>
                </div>
                <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener noreferrer" class="min-h-[44px] px-3.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm font-bold flex items-center gap-1.5 shrink-0">
                    <span class="material-symbols-outlined text-[18px]">chat</span> WhatsApp</a>
            </div>
        @endif
    </div>
</x-app-layout>
