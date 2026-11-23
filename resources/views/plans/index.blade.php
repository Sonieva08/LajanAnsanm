<x-app-layout>
    @php
        $cur = config('lajanansanm.currency');
        $periods = collect(['monthly' => __('Chak mwa'), 'quarterly' => __('3 mwa'), 'yearly' => __('Chak ane')])
            ->filter(fn ($l, $k) => $k === 'monthly' || $plans->contains(fn ($p) => $p->priceFor($k)));
        $needText = [
            'tontines' => __('Ou pa ka kreye yon nouvo sòl sou plan :plan a kounye a.'),
            'members' => __('Ou pa ka ajoute yon lòt manm sou plan :plan a kounye a.'),
        ];
    @endphp

    <div class="space-y-space-lg" x-data="{ period: 'monthly' }">
        <div>
            <h1 class="font-headline-lg-mobile text-headline-lg-mobile">{{ __('Chwazi pi bon plan pou sòl ou yo') }}</h1>
            <p class="text-on-surface-variant">{{ __('Peye sèlman pou sa ou bezwen. Ou ka chanje plan nenpòt ki lè.') }}</p>
        </div>

        @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert-err">{{ $errors->first() }}</div>@endif

        {{-- Limite atteinte --}}
        @if ($limit)
            <div class="card !p-space-lg space-y-space-md border-2 border-secondary/30" data-limit-notice>
                <div class="flex items-start gap-3">
                    <span class="w-12 h-12 shrink-0 rounded-xl bg-error-container text-secondary flex items-center justify-center"><span class="material-symbols-outlined">block</span></span>
                    <div>
                        <h2 class="font-title-lg text-title-lg">
                            {{ in_array($limit['need'], ['tontines', 'members']) ? __('Ou rive nan limit plan ou an') : __('Fonksyon sa a pa nan plan ou an') }}
                        </h2>
                        <p class="text-on-surface-variant">
                            @if (isset($needText[$limit['need']]))
                                {{ str_replace(':plan', $ent->plan->name, $needText[$limit['need']]) }}
                            @else
                                {{ \App\Support\Features::label($limit['need']) }} — {{ __('disponib sou yon plan peyan.') }}
                            @endif
                        </p>
                    </div>
                </div>

                @if (in_array($limit['need'], ['tontines', 'members']) && $limit['max'])
                    <div>
                        <div class="flex justify-between font-label-md text-label-md mb-1">
                            <span>{{ $limit['need'] === 'tontines' ? __('Sòl aktif') : __('Manm sou sòl sa a') }}</span>
                            <span class="tabular-nums">{{ $limit['used'] }} / {{ $limit['max'] }}</span>
                        </div>
                        <div class="h-2.5 bg-slate-200 rounded-full overflow-hidden"><div class="h-2.5 bg-secondary rounded-full" style="width: 100%"></div></div>
                    </div>
                @endif

                @if ($recommended)
                    <div class="rounded-xl bg-surface-container-low p-space-md flex items-center justify-between gap-3">
                        <div>
                            <p class="font-label-sm text-label-sm uppercase text-primary-container">{{ __('Plan rekòmande') }}</p>
                            <p class="font-title-lg text-title-lg">{{ $recommended->name }}</p>
                        </div>
                        <a href="{{ route('plans.request', $recommended) }}" class="btn-primary btn-sm">{{ __('Chwazi') }}</a>
                    </div>
                @endif
            </div>
        @endif

        {{-- État actuel --}}
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge-info"><span class="material-symbols-outlined text-[14px]">verified</span> {{ __('Plan ou an') }} : {{ $ent->plan->name }}</span>
            <a href="{{ route('subscription') }}" class="font-label-md text-label-md text-primary-container underline">{{ __('Wè abònman mwen') }}</a>
        </div>

        <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-space-md">
            @foreach ($plans as $plan)
                @php
                    $isCurrent = $ent->plan->id === $plan->id && $ent->state !== 'owner';
                    $prices = ['monthly' => $plan->priceFor('monthly'), 'quarterly' => $plan->priceFor('quarterly'), 'yearly' => $plan->priceFor('yearly')];
                    $disc = ['quarterly' => $plan->discountPercent('quarterly'), 'yearly' => $plan->discountPercent('yearly')];
                @endphp
                <div class="card !p-space-lg flex flex-col gap-space-md {{ $plan->is_highlighted ? 'border-2 border-primary-container shadow-lg' : '' }}"
                     x-data="{ p: @js($prices), d: @js($disc), months: { monthly: 1, quarterly: 3, yearly: 12 },
                               labels: { monthly: @js(__('mwa')), quarterly: @js(__('3 mwa')), yearly: @js(__('ane')) },
                               billingLabels: { monthly: @js(__('Peye chak mwa')), quarterly: @js(__('Peye chak 3 mwa')), yearly: @js(__('Peye chak ane')) },
                               currency: @js($cur) }"
                     data-plan-pricing="{{ $plan->slug }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            @if ($plan->badge)<span class="badge bg-primary-container text-on-primary mb-1">{{ $plan->badge }}</span>@endif
                            <h3 class="font-title-lg text-title-lg">{{ $plan->name }}</h3>
                        </div>
                        @if ($isCurrent)<span class="badge-paid">{{ __('Plan ou an') }}</span>@endif
                    </div>
                    @if ($plan->description)<p class="font-body-sm text-body-sm text-on-surface-variant -mt-2">{{ $plan->description }}</p>@endif

                    @if (! $plan->is_free && $periods->count() > 1)
                        <div class="inline-flex self-start items-center gap-0.5 rounded-full bg-surface-container-high p-1 font-label-sm text-label-sm" data-period-switch>
                            @foreach ($periods as $key => $label)
                                @if ($key === 'monthly' || $plan->priceFor($key))
                                    <button type="button" @click="period = '{{ $key }}'"
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1.5 transition"
                                            :class="period === '{{ $key }}' ? 'bg-surface-container-lowest text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface'">
                                        {{ $label }}
                                        @if ($key !== 'monthly')
                                            <span class="text-[10px] font-semibold text-emerald-700" x-show="d.{{ $key }}" x-cloak x-text="d.{{ $key }} + '%'"></span>
                                        @endif
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div>
                        @if ($plan->is_free)
                            <p class="font-currency-display text-currency-display tabular-nums">0 <span class="font-title-md text-title-md text-on-surface-variant">{{ $cur }}</span></p>
                        @else
                            <p class="flex flex-wrap items-baseline gap-x-1.5 tabular-nums">
                                <span class="font-currency-display text-currency-display" data-monthly-equivalent
                                      x-text="Number((p[period] ?? p.monthly) / months[period]).toLocaleString('en-US', { maximumFractionDigits: 2 })">{{ number_format((float) $plan->priceFor('monthly'), 0, '.', ',') }}</span>
                                <span class="font-title-md text-title-md text-on-surface-variant">{{ $cur }} / {{ __('mwa') }}</span>
                            </p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1" data-billing-summary
                               x-text="Number(p[period] ?? p.monthly).toLocaleString('en-US', { maximumFractionDigits: 2 }) + ' ' + currency + ' · ' + billingLabels[period]">
                                {{ number_format((float) $plan->priceFor('monthly'), 0, '.', ',') }} {{ $cur }} · {{ __('Peye chak mwa') }}
                            </p>
                        @endif
                    </div>

                    <ul class="space-y-2 font-body-md text-body-md">
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-emerald-500" style="font-variation-settings:'FILL' 1">check_circle</span>
                            {{ $plan->max_active_tontines ? $plan->max_active_tontines . ' ' . __('sòl aktif') : __('Sòl ilimite') }}</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-emerald-500" style="font-variation-settings:'FILL' 1">check_circle</span>
                            {{ __('Jiska') }} {{ $plan->max_members_per_tontine ?? '∞' }} {{ __('manm pa sòl') }}</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-emerald-500" style="font-variation-settings:'FILL' 1">check_circle</span>{{ __('Rapò enprimab (PDF)') }}</li>
                        @foreach (\App\Support\Features::ALL as $key => [$label])
                            @if ($plan->allows($key))
                                <li class="flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-emerald-500" style="font-variation-settings:'FILL' 1">check_circle</span>{{ __($label) }}</li>
                            @else
                                <li class="flex items-center gap-2 text-slate-400 line-through"><span class="material-symbols-outlined text-[20px]">remove_circle</span>{{ __($label) }}</li>
                            @endif
                        @endforeach
                    </ul>

                    <div class="mt-auto">
                        @if ($isCurrent)
                            <span class="btn-secondary w-full opacity-70 pointer-events-none"><span class="material-symbols-outlined text-[18px]">check</span> {{ __('Plan aktif ou') }}</span>
                        @elseif ($plan->is_free)
                            <span class="btn-secondary w-full opacity-60 pointer-events-none">{{ __('Plan gratis') }}</span>
                        @elseif ($plan->isRequestable())
                            <a :href="'{{ route('plans.request', $plan) }}?period=' + (p[period] ? period : 'monthly')" class="{{ $plan->is_highlighted ? 'btn-primary' : 'btn-secondary' }} w-full">
                                {{ __('Chwazi plan sa a') }} <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <details class="card">
            <summary class="font-title-md text-title-md cursor-pointer">{{ __('Kisa k pase si abònman mwen fini?') }}</summary>
            <p class="mt-2 text-on-surface-variant">
                {{ __('Ou gen :days jou pou renouvle san pèdi anyen. Apre sa, ou tounen sou plan gratis la : sòl ak done ou yo rete la, men sa ki depase limit gratis la pase an lekti sèlman jiskaske ou renouvle.', ['days' => $ent->graceDays]) }}
            </p>
        </details>
    </div>
</x-app-layout>
