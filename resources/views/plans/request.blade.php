<x-app-layout>
    @php
        $cur = config('lajanansanm.currency');
        $periodLabel = ['monthly' => __('Chak mwa'), 'quarterly' => __('3 mwa'), 'yearly' => __('Chak ane')];
        $months = \App\Models\Plan::PERIODS[$period];
        $base = ($plan->priceFor('monthly') ?? 0) * $months;
        $saving = max(0, $base - $amount);
        $first = array_key_first($methods);
    @endphp

    <x-page-header :back="route('plans')" :title="__('Mande abònman')" :subtitle="$plan->name . ' · ' . $periodLabel[$period]" />

    @if ($errors->any())<div class="alert-err mb-space-md">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

    <div class="space-y-space-lg">
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
                <span class="badge-info">{{ $plan->max_active_tontines ? $plan->max_active_tontines . ' ' . __('tontin') : __('Tontin ilimite') }}</span>
                <span class="badge-info">{{ $plan->max_members_per_tontine ?? '∞' }} {{ __('manm pa tontin') }}</span>
                @foreach ($plan->features ?? [] as $f)<span class="badge-muted">{{ \App\Support\Features::label($f) }}</span>@endforeach
            </div>
        </div>

        @if (empty($methods))
            <div class="alert-err" data-no-payment>{{ __('Peman yo pa disponib pou kounye a. Kontakte sipò a.') }}</div>
        @else
            <form method="POST" action="{{ route('plans.request.submit', $plan) }}" class="space-y-space-lg" x-data="{ method: @js(old('payment_method', $first)), info: @js($payment) }">
                @csrf
                <input type="hidden" name="billing_period" value="{{ $period }}">

                <div class="card !p-space-lg space-y-space-md">
                    <h2 class="font-title-lg text-title-lg"><span class="inline-flex w-7 h-7 rounded-full bg-primary-container text-on-primary items-center justify-center text-sm mr-1">1</span> {{ __('Voye lajan an') }}</h2>

                    <div class="grid gap-2 {{ [1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3'][min(count($methods), 3)] }}">
                        @foreach ($methods as $key => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="{{ $key }}" x-model="method" class="peer sr-only" required>
                                <span class="block text-center py-3 rounded-xl border-[1.5px] border-slate-200 font-label-lg text-label-lg peer-checked:border-primary-container peer-checked:bg-primary-fixed/60 transition">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="rounded-xl bg-surface-container-low p-space-md space-y-1">
                        <template x-if="method === 'moncash' || method === 'natcash'">
                            <div>
                                <p class="font-label-sm text-label-sm uppercase text-on-surface-variant" x-text="method === 'moncash' ? 'MonCash' : 'NatCash'"></p>
                                <p class="font-headline-md text-headline-md tabular-nums" x-text="method === 'moncash' ? info.moncash_number : info.natcash_number"></p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="method === 'moncash' ? info.moncash_name : info.natcash_name"></p>
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
                        <label class="label" for="payer_phone">{{ __('Nimewo ou te itilize a') }}</label>
                        <input id="payer_phone" name="payer_phone" class="input" value="{{ old('payer_phone') }}" placeholder="+509 ..." required>
                        <x-input-error :messages="$errors->get('payer_phone')" class="mt-1" />
                    </div>
                    <div>
                        <label class="label" for="transaction_ref">{{ __('Kòd tranzaksyon / ID resi SMS') }}</label>
                        <input id="transaction_ref" name="transaction_ref" class="input" value="{{ old('transaction_ref') }}" required>
                        <x-input-error :messages="$errors->get('transaction_ref')" class="mt-1" />
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
