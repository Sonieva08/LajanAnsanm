<x-app-layout>
    @php $cur = config('lajanansanm.currency'); @endphp
    @include('partials.copy-toast')
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

                {{-- Où envoyer l'argent / réponse du bénéficiaire --}}
                @php
                    $p = $cycle->payout;
                    $b = $cycle->beneficiary;
                    $method = $p ? $p->method : $b?->payout_method;
                    $account = $p ? $p->account : $b?->payout_account;
                    $accName = $p ? $p->account_name : $b?->payout_name;
                @endphp
                @if ($method)
                    <div class="bg-surface-container-low rounded-lg p-space-sm flex items-center justify-between gap-space-sm" data-destination>
                        <div class="min-w-0">
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $p ? __('Voye sou') : __('Pou voye lajan an sou') }}</p>
                            <p class="font-label-lg text-label-lg font-semibold truncate tabular-nums">{{ \App\Models\Member::payoutMethodLabel($method) }}@if ($account) · {{ $account }}@endif</p>
                            @if ($accName)<p class="font-body-sm text-body-sm text-on-surface-variant truncate">{{ $accName }}</p>@endif
                        </div>
                        @if ($account)
                            <button type="button" class="h-10 px-3 bg-surface-container rounded-lg text-primary-container font-label-md text-label-md flex items-center gap-1.5 active:scale-95 transition shrink-0"
                                    x-on:click="copyText(@js($account), @js(__('Nimewo kopye!')))"><span class="material-symbols-outlined text-[16px]">content_copy</span>{{ __('Kopye') }}</button>
                        @endif
                    </div>
                @elseif (! $p && $cycle->status === 'open' && $tontine->member_portal_enabled)
                    <p class="font-body-sm text-body-sm text-on-surface-variant" data-no-destination>{{ __('Benefisyè a poko di kijan li vle resevwa lajan an.') }}</p>
                @endif

                @if ($p?->isDisputed())
                    <div class="rounded-xl bg-red-50 p-space-sm space-y-2" data-payout-dispute>
                        <p class="flex items-center gap-1.5 font-title-md text-title-md text-red-950 font-bold"><span class="material-symbols-outlined text-[20px] text-error">report</span>{{ __('Benefisyè a di li pa resevwa lajan an') }}</p>
                        <p class="font-body-md text-body-md text-red-900">{{ $p->member_note }}</p>
                        <form method="POST" action="{{ route('tontines.cycles.payout.reopen', [$tontine, $cycle]) }}">@csrf
                            <button class="btn-secondary btn-sm w-full">{{ __('Mwen verifye. Mande l konfime ankò.') }}</button>
                        </form>
                    </div>
                @elseif ($p?->isReceived())
                    <p class="flex items-center gap-1.5 font-label-md text-label-md text-emerald-800" data-payout-received><span class="material-symbols-outlined text-[16px]">task_alt</span>{{ __('Benefisyè a konfime li resevwa lajan an') }}</p>
                @elseif ($p && $tontine->member_portal_enabled)
                    <p class="flex items-center gap-1.5 font-label-md text-label-md text-amber-700" data-payout-awaiting><span class="material-symbols-outlined text-[16px]">hourglass_top</span>{{ __('Ap tann konfimasyon benefisyè a') }}</p>
                @endif

                @if ($cycle->payout)
                    @if ($p && $p->sender_method)
                        <div class="rounded-lg bg-surface-container-low p-space-sm space-y-1 font-body-sm text-body-sm" data-payout-transfer-details>
                            <p>{{ __('Metòd ki voye a') }}: <strong>{{ \App\Models\Member::payoutMethodLabel($p->sender_method) }}</strong></p>
                            <p>{{ __('Kont ki voye a') }}: <strong class="tabular-nums">{{ $p->sender_phone }}</strong></p>
                            <p>{{ __('Nimewo tranzaksyon an') }}: <strong class="font-mono">{{ $p->transaction_ref }}</strong></p>
                            @if ($p->note)<p>{{ __('Nòt') }}: {{ $p->note }}</p>@endif
                            @if ($p->receiptExists())
                                <a href="{{ route('tontines.cycles.payout.receipt', [$tontine, $cycle]) }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 font-label-md text-label-md text-primary-container underline">
                                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>{{ __('Gade resi transfè a') }}
                                </a>
                            @endif
                        </div>
                    @endif
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ __('Benefisyè a resevwa') }} <strong class="text-on-surface">{{ number_format($cycle->payout->netAmount(), 0, '.', ',') }} {{ $cur }}</strong>@if ((float) $cycle->payout->fee_amount > 0) ({{ __('kòmisyon') }} {{ number_format($cycle->payout->fee_amount, 0, '.', ',') }} {{ $cur }})@endif
                        {{ __('nan dat') }} {{ $cycle->payout->paid_at->format('d/m/Y') }}.
                    </p>
                @elseif ($cycle->status === 'open')
                    @if ($paidCount === 0)
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Pa gen okenn peman pou tou sa a.') }}</p>
                    @else
                        <form method="POST" action="{{ route('tontines.cycles.payout.store', [$tontine, $cycle]) }}" enctype="multipart/form-data" class="space-y-space-sm"
                              onsubmit="return confirm('{{ __('Vèse lajan an bay benefisyè a epi fèmen tou sa a? Sa pa ka anile.') }}')">
                            @csrf
                            <div class="rounded-xl bg-surface-container-low p-space-sm space-y-space-sm" data-payout-transfer-form>
                                <p class="font-label-lg text-label-lg">{{ __('Enfòmasyon sou transfè a') }}</p>
                                @if ($b?->payout_method === 'cash')
                                    <input type="hidden" name="method" value="cash">
                                    <p class="font-body-sm text-body-sm text-on-surface-variant" data-cash-payout>{{ __('Benefisyè a chwazi resevwa lajan kach. Remèt lajan an men nan men.') }}</p>
                                @else
                                <div>
                                    <label class="label" for="payout-method-{{ $cycle->id }}">{{ __('Metòd y ap itilize pou resevwa lajan an') }}</label>
                                    <select id="payout-method-{{ $cycle->id }}" name="method" class="input" required>
                                        @foreach (\App\Models\Member::PAYOUT_METHODS as $option)
                                            <option value="{{ $option }}" @selected(old('method', $b?->payout_method ?? 'moncash') === $option)>
                                                {{ \App\Models\Member::payoutMethodLabel($option) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('method')" class="mt-1" />
                                </div>
                                <div>
                                    <label class="label" for="payout-account-{{ $cycle->id }}">{{ __('Nimewo/kont ki pral resevwa lajan an') }}</label>
                                    <input id="payout-account-{{ $cycle->id }}" name="account" class="input"
                                           value="{{ old('account', $b?->payout_account) }}" maxlength="120">
                                    <x-input-error :messages="$errors->get('account')" class="mt-1" />
                                </div>
                                <div>
                                    <label class="label" for="payout-account-name-{{ $cycle->id }}">{{ __('Non sou kont lan (opsyonèl)') }}</label>
                                    <input id="payout-account-name-{{ $cycle->id }}" name="account_name" class="input"
                                           value="{{ old('account_name', $b?->payout_name) }}" maxlength="100">
                                </div>
                                @endif
                                @if ($b?->payout_method !== 'cash')
                                <div>
                                    <label class="label" for="payout-sender-method-{{ $cycle->id }}">{{ __('Metòd ou itilize pou voye lajan an') }}</label>
                                    <select id="payout-sender-method-{{ $cycle->id }}" name="sender_method" class="input" required>
                                        @foreach (\App\Models\Member::PAYOUT_METHODS as $option)
                                            <option value="{{ $option }}" @selected(old('sender_method', 'moncash') === $option)>
                                                {{ \App\Models\Member::payoutMethodLabel($option) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('sender_method')" class="mt-1" />
                                </div>
                                <div>
                                    <label class="label" for="payout-sender-phone-{{ $cycle->id }}">{{ __('Nimewo/kont ki voye lajan an') }}</label>
                                    <input id="payout-sender-phone-{{ $cycle->id }}" name="sender_phone" type="tel" class="input"
                                           value="{{ old('sender_phone') }}" placeholder="+509 ..." required>
                                    <x-input-error :messages="$errors->get('sender_phone')" class="mt-1" />
                                </div>
                                <div>
                                    <label class="label" for="payout-transaction-{{ $cycle->id }}">{{ __('Nimewo tranzaksyon an') }}</label>
                                    <input id="payout-transaction-{{ $cycle->id }}" name="transaction_ref" class="input"
                                           value="{{ old('transaction_ref') }}" required maxlength="80">
                                    <x-input-error :messages="$errors->get('transaction_ref')" class="mt-1" />
                                </div>
                                <div>
                                    <label class="label" for="payout-receipt-{{ $cycle->id }}">{{ __('Resi transfè a (opsyonèl)') }}</label>
                                    <input id="payout-receipt-{{ $cycle->id }}" name="receipt" type="file"
                                           accept="image/jpeg,image/png,image/webp" class="input">
                                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ __('Voye yon foto JPEG, PNG oswa WebP, 5 Mo maksimòm.') }}</p>
                                    <x-input-error :messages="$errors->get('receipt')" class="mt-1" />
                                </div>
                                <div>
                                    <label class="label" for="payout-note-{{ $cycle->id }}">{{ __('Nòt (opsyonèl)') }}</label>
                                    <textarea id="payout-note-{{ $cycle->id }}" name="note" rows="2" maxlength="300" class="input">{{ old('note') }}</textarea>
                                </div>
                                @endif
                            </div>
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
