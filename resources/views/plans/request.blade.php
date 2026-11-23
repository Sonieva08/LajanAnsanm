<x-app-layout>
    @php
        $cur = config('lajanansanm.currency');
        $periodLabel = ['monthly' => __('Chak mwa'), 'quarterly' => __('3 mwa'), 'yearly' => __('Chak ane')];
        $months = \App\Models\Plan::PERIODS[$period];
        $base = ($plan->priceFor('monthly') ?? 0) * $months;
        $saving = max(0, $base - $amount);
        $first = array_key_first($methods);
        $defaultPaymentChoice = $online && ! old('payment_method') ? 'online' : 'manual';
    @endphp

    <x-page-header :back="route('plans')" :title="__('Mande abònman')" :subtitle="$plan->name . ' · ' . $periodLabel[$period]" />

    @if ($errors->any())<div class="alert-err mb-space-md">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

    <div class="space-y-space-lg" x-data="{ paymentChoice: @js($defaultPaymentChoice) }">
        <div class="card !p-space-lg space-y-2">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="font-title-lg text-title-lg">{{ __('Plan') }} {{ $plan->name }}</h2>
                <p class="font-currency-display text-currency-display tabular-nums text-primary-container">{{ number_format($amount, 0, '.', ',') }} <span class="font-title-md text-title-md">{{ $cur }}</span></p>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Peryòd') }} : {{ $periodLabel[$period] }}</p>
            @if ($saving > 0)
                <p class="flex justify-between font-body-md text-body-md text-emerald-700"><span>{{ __('Rabè') }}</span><span class="tabular-nums">− {{ number_format($saving, 0, '.', ',') }} {{ $cur }}</span></p>
            @endif
            <div class="flex flex-wrap gap-2 pt-1">
                <span class="badge-info">{{ $plan->max_active_tontines ? $plan->max_active_tontines . ' ' . __('sòl') : __('Sòl ilimite') }}</span>
                <span class="badge-info">{{ $plan->max_members_per_tontine ?? '∞' }} {{ __('manm pa sòl') }}</span>
                @foreach ($plan->features ?? [] as $f)<span class="badge-muted">{{ \App\Support\Features::label($f) }}</span>@endforeach
            </div>
        </div>

        @if ($online || ! empty($methods))
            <section class="card !p-space-lg space-y-space-md" data-payment-selector>
                <div>
                    <h2 class="font-title-lg text-title-lg">{{ __('Chwazi metòd peman an') }}</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Chwazi kijan ou vle peye plan sa a.') }}</p>
                </div>
                <div class="grid gap-space-sm {{ $online && ! empty($methods) ? 'sm:grid-cols-2' : 'grid-cols-1' }}" role="group" aria-label="{{ __('Metòd peman') }}">
                    @if ($online)
                        <button type="button" data-payment-option="online"
                                @click="paymentChoice = 'online'"
                                :aria-pressed="paymentChoice === 'online'"
                                :class="paymentChoice === 'online' ? 'border-primary-container bg-primary-fixed/60 ring-2 ring-primary-container/30' : 'border-slate-200 hover:border-primary-container/50'"
                                class="flex items-start gap-3 rounded-xl border-[1.5px] p-space-md text-left transition">
                            <x-payment-method-logo method="moncash" />
                            <span>
                                <span class="block font-label-lg text-label-lg">{{ __('Peye sou entènèt') }}</span>
                                <span class="block font-body-sm text-body-sm text-on-surface-variant">{{ __('MonCash ak aktivasyon otomatik') }}</span>
                            </span>
                        </button>
                    @endif
                    @if (! empty($methods))
                        <button type="button" data-payment-option="manual"
                                @click="paymentChoice = 'manual'"
                                :aria-pressed="paymentChoice === 'manual'"
                                :class="paymentChoice === 'manual' ? 'border-primary-container bg-primary-fixed/60 ring-2 ring-primary-container/30' : 'border-slate-200 hover:border-primary-container/50'"
                                class="flex items-start gap-3 rounded-xl border-[1.5px] p-space-md text-left transition">
                            <span class="flex shrink-0 items-center gap-1">
                                <x-payment-method-logo method="moncash" size="h-8 w-8" />
                                <x-payment-method-logo method="natcash" size="h-8 w-8" />
                            </span>
                            <span>
                                <span class="block font-label-lg text-label-lg">{{ __('Peye manyèlman') }}</span>
                                <span class="block font-body-sm text-body-sm text-on-surface-variant">{{ __('MonCash, NatCash oswa transfè labank') }}</span>
                            </span>
                        </button>
                    @endif
                </div>
            </section>
        @endif

        {{-- Paiement en ligne : activation automatique --}}
        @if ($online)
            <form method="POST" action="{{ route('plans.pay-online', $plan) }}" class="card !p-space-lg space-y-space-md ring-2 ring-primary-container" data-online-payment x-show="paymentChoice === 'online'" x-cloak>
                @csrf
                <input type="hidden" name="billing_period" value="{{ $period }}">
                <div class="flex items-start gap-3">
                    <x-payment-method-logo method="moncash" />
                    <div>
                        <h2 class="font-title-lg text-title-lg">{{ __('Peye ak MonCash') }}</h2>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Aktivasyon otomatik') }}</p>
                    </div>
                </div>
                @if ($sandbox)
                    <p class="alert-err" data-sandbox>{{ __('Mòd tès (Sandbox): pa gen vrè lajan ki bouje.') }}</p>
                @endif
                <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Ou pral ale sou paj peman MonCash. Plan ou aktive otomatikman apre peman an, san ou pa bezwen tann nou.') }}</p>
                <button class="btn-primary w-full"><span class="material-symbols-outlined">lock</span> {{ __('Peye :amount ak MonCash', ['amount' => number_format($amount, 0, '.', ',').' '.$cur]) }}</button>
                <p class="font-body-sm text-body-sm text-on-surface-variant text-center">{{ __('Peman an pase pa MonCashConnect, yon sèvis endepandan ki pa afilye ak Digicel.') }}</p>
            </form>

            @if (! empty($methods))
                <p class="text-center font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">{{ __('oswa peye alamen') }}</p>
            @endif
        @endif

        @if (empty($methods) && ! $online)
            <div class="alert-err" data-no-payment>{{ __('Peman yo pa disponib pou kounye a. Kontakte sipò a.') }}</div>
        @elseif (! empty($methods))
            <form method="POST" action="{{ route('plans.request.submit', $plan) }}" enctype="multipart/form-data" class="space-y-space-lg" x-data="{ method: @js(old('payment_method', $first)), info: @js($payment) }" x-show="paymentChoice === 'manual'" x-cloak data-manual-payment>
                @csrf
                <input type="hidden" name="billing_period" value="{{ $period }}">

                <div class="card !p-space-lg space-y-space-md">
                    <h2 class="font-title-lg text-title-lg"><span class="inline-flex w-7 h-7 rounded-full bg-primary-container text-on-primary items-center justify-center text-sm mr-1">1</span> {{ __('Voye lajan an') }}</h2>

                    <div class="grid gap-2 {{ [1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3'][min(count($methods), 3)] }}">
                        @foreach ($methods as $key => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="{{ $key }}" x-model="method" class="peer sr-only" required>
                                <span class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-xl border-[1.5px] border-slate-200 py-2 font-label-lg text-label-lg peer-checked:border-primary-container peer-checked:bg-primary-fixed/60 transition">
                                    @if ($key === 'moncash' || $key === 'natcash')
                                        <x-payment-method-logo :method="$key" size="h-8 w-8" />
                                    @endif
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="rounded-xl bg-surface-container-low p-space-md space-y-1">
                        <template x-if="method === 'moncash' || method === 'natcash'">
                            <div>
                                <div class="flex items-center gap-2">
                                    <template x-if="method === 'moncash'"><x-payment-method-logo method="moncash" size="h-8 w-8" /></template>
                                    <template x-if="method === 'natcash'"><x-payment-method-logo method="natcash" size="h-8 w-8" /></template>
                                    <p class="font-label-sm text-label-sm uppercase text-on-surface-variant" x-text="method === 'moncash' ? 'MonCash' : 'NatCash'"></p>
                                </div>
                                <p class="font-headline-md text-headline-md tabular-nums" x-text="method === 'moncash' ? info.moncash_number : info.natcash_number"></p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="method === 'moncash' ? info.moncash_name : info.natcash_name"></p>
                                <template x-if="method === 'moncash' && info.moncash_qr_path">
                                    <img src="{{ route('subscription.payment-qr', 'moncash') }}" alt="{{ __('QR MonCash pou peye') }}" class="mx-auto mt-3 h-48 w-48 rounded-lg border bg-white object-contain">
                                </template>
                                <template x-if="method === 'natcash' && info.natcash_qr_path">
                                    <img src="{{ route('subscription.payment-qr', 'natcash') }}" alt="{{ __('QR NatCash pou peye') }}" class="mx-auto mt-3 h-48 w-48 rounded-lg border bg-white object-contain">
                                </template>
                            </div>
                        </template>
                        <template x-if="method === 'bank'"><p class="whitespace-pre-line font-body-md text-body-md" x-text="info.bank_info"></p></template>
                    </div>

                    <div class="rounded-xl border border-primary-container/30 bg-primary-fixed/40 p-space-md">
                        <p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Kòd referans obligatwa pou nòt la') }}</p>
                        <p class="font-headline-md text-headline-md tracking-wide text-primary-container" data-reference-code>{{ $code }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ __('Mete kòd sa a nan nòt transfè a pou nou ka idantifye peman ou an.') }}</p>
                    </div>

                    <p class="flex justify-between font-title-md text-title-md"><span>{{ __('Montan pou voye') }}</span><span class="tabular-nums text-primary-container">{{ number_format($amount, 0, '.', ',') }} {{ $cur }}</span></p>
                    @if (filled($payment['instructions']))<p class="font-body-sm text-body-sm text-on-surface-variant">{{ $payment['instructions'] }}</p>@endif
                </div>

                <div class="card !p-space-lg space-y-space-md">
                    <h2 class="font-title-lg text-title-lg"><span class="inline-flex w-7 h-7 rounded-full bg-primary-container text-on-primary items-center justify-center text-sm mr-1">2</span> {{ __('Mwen fin voye lajan an') }}</h2>
                    <div>
                        <label class="label" for="payer_phone">{{ __('Nimewo kont ki te voye lajan an (opsyonèl)') }}</label>
                        <input id="payer_phone" name="payer_phone" class="input" value="{{ old('payer_phone') }}" placeholder="+509 ...">
                        <x-input-error :messages="$errors->get('payer_phone')" class="mt-1" />
                    </div>
                    <div>
                        <label class="label" for="recipient_account">{{ __('Nimewo/kont ki te resevwa lajan an') }}</label>
                        <input id="recipient_account" name="recipient_account" class="input"
                               value="{{ old('recipient_account') }}" required maxlength="120"
                               placeholder="{{ __('Antre nimewo oswa kont destinasyon an') }}">
                        <x-input-error :messages="$errors->get('recipient_account')" class="mt-1" />
                    </div>
                    <div>
                        <label class="label" for="transaction_ref">{{ __('ID tranzaksyon (obligatwa pou MonCash/NatCash; opsyonèl pou labank)') }}</label>
                        <input id="transaction_ref" name="transaction_ref" class="input" value="{{ old('transaction_ref') }}" x-bind:required="method !== 'bank'">
                        <x-input-error :messages="$errors->get('transaction_ref')" class="mt-1" />
                    </div>
                    <div>
                        <label class="label" for="receipt">{{ __('Foto resi tranzaksyon an (obligatwa)') }}</label>
                        <input id="receipt" name="receipt" type="file" accept="image/jpeg,image/png,image/webp" class="input" required
                               aria-describedby="receipt-help">
                        <p id="receipt-help" class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ __('Voye yon foto JPEG, PNG oswa WebP, 5 Mo maksimòm.') }}</p>
                        <x-input-error :messages="$errors->get('receipt')" class="mt-1" />
                    </div>
                    <div>
                        <label class="label" for="note">{{ __('Nòt (opsyonèl)') }}</label>
                        <input id="note" name="note" class="input" maxlength="300" value="{{ old('note') }}">
                    </div>
                    <button class="btn-primary w-full">{{ __('Mwen peye deja') }} <span class="material-symbols-outlined">arrow_forward</span></button>
                    <p class="font-body-sm text-body-sm text-on-surface-variant text-center">{{ __('Nou verifye peman an epi nou aktive plan ou an. Sa ka pran jiska 24 èdtan.') }}</p>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
