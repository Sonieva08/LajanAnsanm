<x-portal-layout :title="$tontine->name" :member="$member">
    @php
        $cur = config('lajanansanm.currency');
        $fmt = fn ($n) => number_format((float) $n, 0, '.', ',').' '.$cur;
        $period = $tontine->period_type === 'weekly' ? __('chak semèn') : __('chak mwa');
        $digits = fn ($v) => preg_replace('/\D+/', '', (string) $v);
    @endphp

    @include('partials.copy-toast')

    @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert-err">{{ $errors->first() }}</div>@endif

    {{-- Salutation --}}
    <section class="card space-y-space-sm">
        <div class="flex items-center justify-between gap-space-sm">
            <h1 class="font-headline-lg-mobile text-headline-lg-mobile truncate">{{ __('Bonjou') }}, {{ $member->name }}</h1>
            <span class="badge bg-primary-fixed text-primary-container shrink-0"><span class="material-symbols-outlined text-[14px]">verified_user</span>{{ __('Manm aktif') }}</span>
        </div>
        <div class="space-y-1">
            <p class="flex items-center gap-1.5 font-title-md text-title-md"><span class="material-symbols-outlined text-[18px] text-primary-container">groups</span>{{ $tontine->name }}</p>
            <p class="flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant tabular-nums">
                <span class="material-symbols-outlined text-[16px]">payments</span>
                {{ __('Kotizasyon') }}: <strong class="text-primary-container">{{ $fmt($tontine->amount_per_period) }}</strong> / {{ $period }}
            </p>
        </div>
    </section>

    {{-- Mon versement : confirmer la réception --}}
    @if ($myPayout)
        @php
            $net = $myPayout->netAmount();
            $dest = $myPayout->method ? \App\Models\Member::payoutMethodLabel($myPayout->method).($myPayout->account ? ' · '.$myPayout->account : '').($myPayout->account_name ? ' ('.$myPayout->account_name.')' : '') : null;
        @endphp

        @if ($myPayout->isReceived())
            <article class="bg-tertiary-container/10 rounded-xl p-space-md text-center space-y-space-sm" data-payout-card="received">
                <span class="mx-auto w-14 h-14 rounded-full bg-on-tertiary-container text-on-primary flex items-center justify-center shadow-sm"><span class="material-symbols-outlined text-[32px]">task_alt</span></span>
                <h2 class="font-headline-md text-headline-md text-on-tertiary-container">{{ __('Ou resevwa lajan ou') }}</h2>
                <p class="inline-flex items-center gap-1 bg-surface-container-lowest px-space-md py-1.5 rounded-full shadow-sm font-title-md text-title-md text-primary tabular-nums">
                    {{ $fmt($net) }}<span class="font-body-md text-body-md text-on-surface-variant font-medium">· {{ __('Tou') }} {{ $myTurn?->cycle_number }}</span>
                </p>
                <p class="font-body-sm text-body-sm text-on-surface-variant max-w-[280px] mx-auto">
                    {{ __('Tranzaksyon sa a fini ak siksè nan dat') }} {{ ($myPayout->member_responded_at ?? $myPayout->paid_at)->format('d/m/Y') }}. {{ __('Mèsi paske w fè pati kominote a!') }}
                </p>
            </article>
        @elseif ($myPayout->isDisputed())
            <article class="bg-error-container/40 rounded-xl p-space-md space-y-space-sm" data-payout-card="disputed">
                <div class="flex items-start gap-space-sm">
                    <span class="w-9 h-9 rounded-full bg-error text-on-error flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-[20px]">warning</span></span>
                    <div class="min-w-0">
                        <p class="font-title-md text-title-md text-on-error-container leading-tight">{{ __('Ou di ou pa resevwa lajan an') }}</p>
                        <p class="font-body-sm text-body-sm text-error">{{ __('Tou') }} {{ $myTurn?->cycle_number }}</p>
                    </div>
                </div>
                <p class="bg-surface-container-lowest/80 rounded-lg p-space-sm font-body-md text-body-md italic">« {{ $myPayout->member_note }} »</p>
                <p class="flex items-center gap-2 bg-error-container/60 px-space-sm py-space-xs rounded-lg font-body-sm text-body-sm text-on-error-container font-medium">
                    <span class="material-symbols-outlined text-[18px] text-error">info</span>{{ __('Òganizatè a gen pou l verifye ak ou.') }}
                </p>
                <form method="POST" action="{{ route('portal.payout.confirm', $myPayout) }}">@csrf
                    <button class="w-full h-12 bg-on-tertiary-container text-on-primary rounded-lg flex items-center justify-center gap-2 font-label-lg text-label-lg active:scale-[0.99] transition shadow-sm">
                        <span class="material-symbols-outlined text-[20px]">check_circle</span>{{ __('Mwen resevwa l finalman') }}
                    </button>
                </form>
            </article>
        @else
            <article class="card space-y-space-md" data-payout-card="awaiting">
                <div class="flex items-center justify-between gap-space-xs">
                    <span class="inline-flex items-center gap-1 bg-tertiary-fixed text-on-tertiary-fixed px-space-sm py-0.5 rounded-full font-label-sm text-label-sm uppercase">
                        <span class="material-symbols-outlined text-[16px]">celebration</span>{{ __('Tou ou a rive') }}
                    </span>
                    <span class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">{{ $myPayout->paid_at->format('d/m/Y') }}</span>
                </div>

                <div class="bg-surface-container-low rounded-lg p-space-sm">
                    <p class="font-body-sm text-body-sm text-on-surface-variant mb-1">{{ __('Òganizatè a di li voye') }}:</p>
                    <p class="font-currency-display text-currency-display text-primary tabular-nums">{{ $fmt($net) }}</p>
                    @if ($dest)
                        <p class="flex items-center gap-1 text-on-surface-variant mt-1.5 font-body-sm text-body-sm font-medium tabular-nums">
                            <span class="material-symbols-outlined text-[18px] text-primary">phone_iphone</span>{{ __('Voye sou') }}: {{ $dest }}
                        </p>
                    @endif
                    @if ($myPayout->transaction_ref)
                        <div class="mt-2 space-y-1 border-t border-surface-container pt-2 font-body-sm text-body-sm text-on-surface-variant" data-payout-transfer-details>
                            <p>{{ __('Metòd ki voye a') }}: <strong>{{ \App\Models\Member::payoutMethodLabel($myPayout->sender_method) }}</strong></p>
                            <p>{{ __('Kont ki voye a') }}: <strong class="tabular-nums">{{ $myPayout->sender_phone }}</strong></p>
                            <p>{{ __('Nimewo tranzaksyon an') }}: <strong class="font-mono">{{ $myPayout->transaction_ref }}</strong></p>
                            @if ($myPayout->note)<p>{{ __('Nòt') }}: {{ $myPayout->note }}</p>@endif
                            @if ($myPayout->receiptExists())
                                <a href="{{ route('portal.payout.receipt', $myPayout) }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 font-label-md text-label-md text-primary-container underline">
                                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>{{ __('Gade resi transfè a') }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="space-y-space-sm">
                    <form method="POST" action="{{ route('portal.payout.confirm', $myPayout) }}">@csrf
                        <button class="w-full h-12 bg-on-tertiary-container text-on-primary rounded-lg flex items-center justify-center gap-2 font-label-lg text-label-lg active:scale-[0.99] transition shadow-sm">
                            <span class="material-symbols-outlined text-[20px]">check_circle</span>{{ __('Mwen resevwa lajan an') }}
                        </button>
                    </form>

                    <details>
                        <summary class="w-full h-12 bg-error-container text-error rounded-lg flex items-center justify-center gap-2 font-label-lg text-label-lg cursor-pointer list-none">
                            <span class="material-symbols-outlined text-[20px]">report_problem</span>{{ __('Mwen pa resevwa l') }}
                        </summary>
                        <form method="POST" action="{{ route('portal.payout.dispute', $myPayout) }}" class="mt-space-xs bg-error-container/30 p-space-sm rounded-lg space-y-space-xs">
                            @csrf
                            <label class="flex items-center gap-1 font-label-md text-label-md text-error" for="member_note"><span class="material-symbols-outlined text-[16px]">edit_note</span>{{ __('Di nou sa k pase a') }}</label>
                            <textarea id="member_note" name="member_note" rows="3" maxlength="300" required
                                      class="w-full rounded-lg border-0 bg-surface-container-lowest p-space-sm font-body-md text-body-md focus:ring-2 focus:ring-error/30 resize-none"
                                      placeholder="{{ __('Egzanp: Mwen poko wè anyen sou MonCash mwen.') }}"></textarea>
                            <button class="w-full h-12 bg-error text-on-error rounded-lg flex items-center justify-center gap-2 font-label-lg text-label-lg active:scale-[0.99] transition shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">send</span>{{ __('Voye bay òganizatè a') }}
                            </button>
                        </form>
                    </details>
                </div>
            </article>
        @endif
    @endif

    @if ($cycles->isEmpty())
        <div class="card !p-space-lg text-center space-y-space-sm">
            <span class="material-symbols-outlined text-[36px] text-on-surface-variant">event_upcoming</span>
            <p class="text-on-surface-variant">{{ __('Kalandriye a poko pare. Òganizatè a ap jenere li talè konsa.') }}</p>
        </div>
    @else
        @if ($openCycle)
            @if ($dueNow)
                @php
                    $daysLeft = (int) today()->diffInDays($openCycle->deadline, false);
                    $total = $cycles->count();
                @endphp
                {{-- À payer maintenant --}}
                <section class="card space-y-space-sm bg-gradient-to-br from-surface-container-lowest via-surface-container-lowest to-primary-fixed/40">
                    <div class="flex items-center justify-between gap-space-sm">
                        <span class="badge bg-primary-fixed text-primary-container uppercase">{{ __('Pou peye kounye a') }}</span>
                        @if ($daysLeft < 0)
                            <span class="badge-late"><span class="material-symbols-outlined text-[14px]">schedule</span>{{ __('An reta') }}</span>
                        @else
                            <span class="badge-wait"><span class="material-symbols-outlined text-[14px]">schedule</span>{{ $daysLeft === 0 ? __('Dènye jou a') : __('Rete') . ' ' . $daysLeft . ' ' . __('jou') }}</span>
                        @endif
                    </div>
                    <p class="font-currency-display text-currency-display text-primary-container tabular-nums">{{ $fmt($tontine->amount_per_period) }}</p>
                    <div class="flex items-center justify-between pt-2 border-t border-surface-container font-label-md text-label-md text-on-surface-variant tabular-nums">
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary-container">repeat</span>{{ __('Tou') }} {{ $openCycle->cycle_number }} {{ __('sou') }} {{ $total }}</span>
                        <span class="font-medium {{ $daysLeft < 0 ? 'text-error' : '' }}">{{ __('Dat limit') }}: {{ $openCycle->deadline->format('d/m/Y') }}</span>
                    </div>
                </section>

                {{-- Où envoyer l'argent --}}
                @if ($methods)
                    <section class="card space-y-space-md" data-payment-info>
                        <div>
                            <h2 class="flex items-center gap-space-xs font-title-md text-title-md"><span class="material-symbols-outlined text-[20px] text-primary-container">send_to_mobile</span>{{ __('Kote pou voye lajan an') }}</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Chwazi metòd la, eskane QR la pou MonCash oswa NatCash, epi voye lajan an.') }}</p>
                        </div>

                        @if (isset($methods['moncash']))
                            <div class="bg-surface-container-low rounded-lg p-space-sm space-y-space-sm">
                                <div class="flex items-center justify-between gap-space-sm">
                                    <div class="flex items-center gap-space-sm min-w-0">
                                        <x-payment-method-logo method="moncash" />
                                        <div class="min-w-0">
                                            <p class="font-title-md text-title-md truncate">MonCash</p>
                                            <p class="font-body-sm text-body-sm text-on-surface-variant truncate tabular-nums">{{ $payment['moncash_number'] }}@if ($payment['moncash_name']) · {{ $payment['moncash_name'] }}@endif</p>
                                        </div>
                                    </div>
                                    <button type="button" class="h-10 px-3 bg-surface-container rounded-lg text-primary-container font-label-md text-label-md flex items-center gap-1.5 active:scale-95 transition shrink-0"
                                            x-on:click="copyText(@js($digits($payment['moncash_number'])), @js(__('Nimewo MonCash kopye!')))">
                                        <span class="material-symbols-outlined text-[16px]">content_copy</span>{{ __('Kopye') }}
                                    </button>
                                </div>
                                <img src="{{ route('portal.payment-qr', 'moncash') }}" alt="{{ __('QR MonCash pou peye') }}" class="h-36 w-36 rounded-lg border bg-white object-contain">
                            </div>
                        @endif

                        @if (isset($methods['natcash']))
                            <div class="bg-surface-container-low rounded-lg p-space-sm space-y-space-sm">
                                <div class="flex items-center justify-between gap-space-sm">
                                    <div class="flex items-center gap-space-sm min-w-0">
                                        <x-payment-method-logo method="natcash" />
                                        <div class="min-w-0">
                                            <p class="font-title-md text-title-md truncate">NatCash</p>
                                            <p class="font-body-sm text-body-sm text-on-surface-variant truncate tabular-nums">{{ $payment['natcash_number'] }}@if ($payment['natcash_name']) · {{ $payment['natcash_name'] }}@endif</p>
                                        </div>
                                    </div>
                                    <button type="button" class="h-10 px-3 bg-surface-container rounded-lg text-primary-container font-label-md text-label-md flex items-center gap-1.5 active:scale-95 transition shrink-0"
                                            x-on:click="copyText(@js($digits($payment['natcash_number'])), @js(__('Nimewo NatCash kopye!')))">
                                        <span class="material-symbols-outlined text-[16px]">content_copy</span>{{ __('Kopye') }}
                                    </button>
                                </div>
                                <img src="{{ route('portal.payment-qr', 'natcash') }}" alt="{{ __('QR NatCash pou peye') }}" class="h-36 w-36 rounded-lg border bg-white object-contain">
                            </div>
                        @endif

                        @if (isset($methods['bank']))
                            <div class="bg-surface-container-low rounded-lg p-space-sm flex items-start justify-between gap-space-sm">
                                <div class="flex items-start gap-space-sm min-w-0">
                                    <span class="w-10 h-10 rounded-full bg-primary-fixed text-primary-container flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-[20px]">account_balance</span></span>
                                    <div class="min-w-0">
                                        <p class="font-title-md text-title-md">{{ __('Labank') }}</p>
                                        <p class="font-body-sm text-body-sm text-on-surface-variant whitespace-pre-line break-words">{{ $payment['bank_info'] }}</p>
                                    </div>
                                </div>
                                <button type="button" class="h-10 px-3 bg-surface-container rounded-lg text-primary-container font-label-md text-label-md flex items-center gap-1.5 active:scale-95 transition shrink-0"
                                        x-on:click="copyText(@js($payment['bank_info']), @js(__('Enfòmasyon labank kopye!')))">
                                    <span class="material-symbols-outlined text-[16px]">content_copy</span>{{ __('Kopye') }}
                                </button>
                            </div>
                        @endif

                        @if ($payment['instructions'])
                            <p class="font-body-sm text-body-sm text-on-surface-variant whitespace-pre-line">{{ $payment['instructions'] }}</p>
                        @endif
                    </section>
                @endif

                {{-- Déclaration du paiement --}}
                @if ($pending)
                    <section class="rounded-xl bg-amber-50 p-space-md space-y-2" data-declaration-pending>
                        <div class="flex items-center gap-2 text-amber-900">
                            <span class="material-symbols-outlined text-[22px] text-amber-600">hourglass_top</span>
                            <p class="font-title-md text-title-md font-bold">{{ __('Ap tann konfimasyon') }}</p>
                        </div>
                        <p class="font-body-sm text-body-sm text-amber-800">{{ __('Ou deklare peman an. Òganizatè a ap verifye li.') }}</p>
                        <p class="font-label-md text-label-md text-amber-700 tabular-nums">{{ $pending->methodLabel() }} · {{ $pending->transaction_ref }}</p>
                        @if ($pending->hasReceipt())
                            <div class="pt-1">
                                <a href="{{ route('portal.declarations.receipt', $pending) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-amber-900 font-semibold underline">
                                    <span class="material-symbols-outlined text-[15px]">image</span> {{ __('Gade resi ou te voye a') }}
                                </a>
                            </div>
                        @endif
                    </section>
                @elseif ($methods)
                    @if ($rejected)
                        <section class="rounded-xl bg-red-50 p-space-md space-y-3" data-declaration-rejected>
                            <div class="flex items-center gap-2 text-red-950">
                                <span class="material-symbols-outlined text-[22px] text-error">error</span>
                                <p class="font-title-md text-title-md font-bold">{{ __('Òganizatè a pa t aksepte deklarasyon an') }}</p>
                            </div>
                            <p class="font-body-md text-body-md text-red-900">{{ $rejected->review_note }}</p>
                            <a href="#declare-form" class="btn-danger btn-sm"><span class="material-symbols-outlined text-[16px]">edit</span>{{ __('Modifye epi voye ankò') }}</a>
                        </section>
                    @endif

                    <form id="declare-form" method="POST" action="{{ route('portal.declare', $openCycle) }}" enctype="multipart/form-data" class="card space-y-space-md" data-declare-form x-data="{ method: @js(old('payment_method', array_key_first($methods))) }">
                        @csrf
                        <div>
                            <h2 class="flex items-center gap-space-xs font-title-md text-title-md"><span class="material-symbols-outlined text-[20px] text-tertiary-container">verified</span>{{ __('Mwen peye') }} — {{ __('Deklare peman ou') }}</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Mete detay transfè ou a pou òganizatè a ka verifye li byen vit.') }}</p>
                        </div>

                        <fieldset class="space-y-1.5">
                            <legend class="label">{{ __('Ki jan ou peye?') }}</legend>
                            <div class="grid grid-flow-col auto-cols-fr gap-space-xs">
                                @foreach ($methods as $key => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="payment_method" value="{{ $key }}" class="peer sr-only" x-model="method" required @checked(old('payment_method', array_key_first($methods)) === $key)>
                                        <span class="h-14 rounded-lg bg-surface-container text-on-surface-variant font-label-md text-label-md flex flex-col items-center justify-center gap-1 transition
                                                     peer-checked:bg-primary-container peer-checked:text-on-primary peer-focus-visible:ring-4 peer-focus-visible:ring-primary-container/20">
                                            @if ($key === 'moncash' || $key === 'natcash')
                                                <x-payment-method-logo :method="$key" size="h-7 w-7" />
                                            @endif
                                            {{ $label }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div>
                            <label class="label" for="payer_phone">{{ __('Nimewo ou te peye ak li a (opsyonèl)') }}</label>
                            <input id="payer_phone" name="payer_phone" type="tel" class="input" value="{{ old('payer_phone', $member->phone) }}" placeholder="+509 ...">
                        </div>
                        <div>
                            <label class="label" for="transaction_ref">{{ __('ID tranzaksyon (obligatwa pou MonCash/NatCash; opsyonèl pou labank)') }}</label>
                            <input id="transaction_ref" name="transaction_ref" class="input uppercase" value="{{ old('transaction_ref') }}" placeholder="MC-98234812" x-bind:required="method !== 'bank'" maxlength="80">
                        </div>
                        <div>
                            <label class="label" for="receipt">{{ __('Foto resi peman an (obligatwa)') }}</label>
                            <input id="receipt" name="receipt" type="file" accept="image/png,image/jpeg,image/webp" class="input !py-2.5 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-primary-container file:text-on-primary hover:file:opacity-90 cursor-pointer" required>
                            <p class="font-body-xs text-body-xs text-on-surface-variant mt-1">{{ __('Voye yon foto resi a pou MonCash, NatCash ak labank. Max 5 Mo.') }}</p>
                        </div>
                        <div>
                            <label class="label" for="note">{{ __('Nòt pou òganizatè a (opsyonèl)') }}</label>
                            <textarea id="note" name="note" rows="2" maxlength="300" class="input !min-h-0 py-3">{{ old('note') }}</textarea>
                        </div>

                        <button class="btn-primary w-full"><span class="material-symbols-outlined">send</span> {{ __('Voye deklarasyon an') }}</button>
                    </form>
                @endif
            @else
                <section class="rounded-xl bg-emerald-50 p-space-md flex items-center gap-3">
                    <span class="material-symbols-outlined text-[26px] text-tertiary-container">check_circle</span>
                    <div>
                        <p class="font-title-md text-title-md text-emerald-950 font-bold">{{ __('Ou peye pou tou') }} {{ $openCycle->cycle_number }}</p>
                        <p class="font-body-sm text-body-sm text-emerald-900">{{ __('Mèsi anpil!') }}</p>
                    </div>
                </section>
            @endif
        @endif

        {{-- Comment je veux recevoir mon tour --}}
        @if ($myTurn && ! $myPayout)
            @php
                $has = $member->hasPayoutDestination();
                $chips = [
                    'moncash' => ['smartphone', 'Digicel'],
                    'natcash' => ['contactless', 'Natcom'],
                    'bank' => ['account_balance', __('Virement')],
                    'cash' => ['payments', __('Men nan men')],
                ];
                $current = old('payout_method', $member->payout_method);
                $organizer = $tontine->admin?->name;
            @endphp

            <section class="space-y-space-sm" data-destination-form-wrap>
                <div class="px-1">
                    <h2 class="font-headline-lg-mobile text-headline-lg-mobile text-primary">{{ __('Kijan ou vle resevwa lajan ou?') }}</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-1">{{ __('Chwazi mwayen ou pi alèz pou resevwa lajan ou lè tou ou a rive.') }}</p>
                </div>

                <div class="card space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-primary text-[22px]">savings</span></span>
                            <div class="min-w-0">
                                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">{{ __('Tou pa w') }}</p>
                                <p class="font-title-md text-title-md truncate tabular-nums">{{ __('Tou') }} {{ $myTurn->cycle_number }} · {{ $myTurn->deadline->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        <p class="font-currency-display text-[28px] leading-9 text-primary tabular-nums shrink-0">{{ $fmt($tontine->netPotAmount()) }}</p>
                    </div>

                    @if ($has)
                        <div class="bg-surface-container-low rounded-lg p-3 space-y-1.5" data-destination-saved>
                            <p class="flex items-center gap-2 font-label-lg text-label-lg">
                                <span class="w-5 h-5 rounded-full bg-tertiary-fixed flex items-center justify-center text-on-tertiary-fixed shrink-0"><span class="material-symbols-outlined text-[14px]">check</span></span>
                                {{ __('Anrejistre') }}: <strong class="text-primary font-semibold tabular-nums">{{ \App\Models\Member::payoutMethodLabel($member->payout_method) }}@if ($member->payout_account) · {{ $member->payout_account }}@endif</strong>
                            </p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant pl-7">{{ __('Ou ka modifye li nenpòt kilè anvan òganizatè a vèse tou ou a.') }}</p>
                        </div>
                    @endif

                    <div class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-on-tertiary-container text-[18px] shrink-0 mt-0.5">tips_and_updates</span>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Di òganizatè a ki kote pou l voye lajan an lè tou ou a rive pou pa gen reta.') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('portal.payout.destination') }}" class="card space-y-space-md" data-destination-form>
                    @csrf
                    <h3 class="font-headline-md text-headline-md">{{ __('Chwazi kote pou resevwa lajan an') }}</h3>

                    <fieldset>
                        <legend class="sr-only">{{ __('Metòd') }}</legend>
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach ($chips as $key => [$icon, $sub])
                                <label class="relative block cursor-pointer">
                                    <input type="radio" name="payout_method" value="{{ $key }}" class="peer sr-only" required @checked($current === $key)>
                                    <span class="flex items-center gap-2 p-3 pr-9 rounded-xl bg-surface-container-low text-on-surface transition active:scale-[0.98]
                                                 peer-checked:bg-primary-fixed peer-checked:text-primary peer-checked:shadow-sm peer-focus-visible:ring-4 peer-focus-visible:ring-primary-container/20">
                                        <span class="w-8 h-8 rounded-full bg-surface-container-lowest flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-[20px]">{{ $icon }}</span></span>
                                        <span class="min-w-0">
                                            <span class="block font-title-md text-title-md font-semibold leading-tight truncate">{{ \App\Models\Member::payoutMethodLabel($key) }}</span>
                                            <span class="block font-label-sm text-label-sm opacity-80">{{ $sub }}</span>
                                        </span>
                                    </span>
                                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[20px] text-transparent peer-checked:text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div id="payout-account-wrap" class="space-y-1 transition-opacity">
                        <label class="label flex items-center justify-between" for="payout_account">
                            <span id="payout-account-label">{{ __('Nimewo oswa kont') }}</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant font-normal uppercase">{{ __('Obligatwa') }}</span>
                        </label>
                        <input id="payout_account" name="payout_account" class="input" value="{{ old('payout_account', $member->payout_account) }}" placeholder="3712-4589" maxlength="120">
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Pa nesesè si ou chwazi lajan kach.') }}</p>
                    </div>

                    <div data-payout-name-wrap @if ($current === 'cash') hidden @endif class="space-y-1">
                        <label class="label flex items-center justify-between" for="payout_name">
                            <span>{{ __('Non sou kont lan') }}</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant font-normal uppercase">{{ __('Opsyonèl') }}</span>
                        </label>
                        <input id="payout_name" name="payout_name" class="input" value="{{ old('payout_name', $member->payout_name) }}" maxlength="100">
                    </div>

                    <button class="btn-primary w-full"><span class="material-symbols-outlined">check</span> {{ __('Sove') }}</button>
                </form>

                <div class="card space-y-3">
                    <p class="flex items-center gap-2 font-title-md text-title-md"><span class="material-symbols-outlined text-primary text-[20px]">verified_user</span>{{ __('Kijan transfè a ap fèt?') }}</p>
                    <div class="space-y-2.5">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-full bg-surface-container-high flex items-center justify-center shrink-0 mt-0.5 font-label-sm text-label-sm text-primary">1</span>
                            <p class="font-body-md text-body-md">{{ __('Òganizatè a ap voye lajan an dirèkteman sou kont sa a lè tou ou a rive.') }}</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-full bg-surface-container-high flex items-center justify-center shrink-0 mt-0.5 font-label-sm text-label-sm text-primary">2</span>
                            <p class="font-body-md text-body-md">{{ __('Lè lajan an voye, yon kat parèt sou paj sa a pou ou konfime ou resevwa l.') }}</p>
                        </div>
                    </div>
                    @if ($organizer)
                        <div class="bg-surface-container-low rounded-lg p-3 flex items-center gap-2.5">
                            <x-avatar :name="$organizer" />
                            <div class="min-w-0">
                                <p class="font-title-md text-title-md truncate">{{ $organizer }}</p>
                                <p class="font-label-sm text-label-sm text-on-surface-variant">{{ __('Òganizatè') }} · {{ $tontine->name }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <script nonce="{{ request()->attributes->get('csp_nonce') }}">
                    (function () {
                        const form = document.querySelector('[data-destination-form]');
                        const account = document.getElementById('payout_account');
                        const wrap = document.getElementById('payout-account-wrap');
                        const nameWrap = form.querySelector('[data-payout-name-wrap]');
                        const label = document.getElementById('payout-account-label');
                        const text = {
                            moncash: [@js(__('Nimewo telefòn MonCash')), '3712-4589'],
                            natcash: [@js(__('Nimewo telefòn NatCash')), '4212-7890'],
                            bank: [@js(__('Nimewo kont labank')), '102-99384-21'],
                            cash: [@js(__('Nimewo oswa kont')), @js(__('Lajan an ap remèt an kach, men nan men.'))],
                        };
                        function apply() {
                            const checked = form.querySelector('input[name=payout_method]:checked');
                            if (!checked) return;
                            const t = text[checked.value];
                            const cash = checked.value === 'cash';
                            label.textContent = t[0];
                            account.placeholder = t[1];
                            account.disabled = cash;
                            account.required = !cash;
                            wrap.style.opacity = cash ? '0.4' : '1';
                            nameWrap.hidden = cash;
                        }
                        form.addEventListener('change', apply);
                        apply();
                    })();
                </script>
            </section>
        @endif

        {{-- Calendrier --}}
        <section class="card space-y-space-md">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-title-md text-title-md">{{ __('Kalandriye tou yo') }}</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">{{ $cycles->count() }} {{ __('manm') }} · {{ $fmt($tontine->netPotAmount()) }} {{ __('chak tou') }}</p>
                    <p class="font-body-xs text-body-xs text-on-surface-variant">{{ __('Chak tou gen yon sèl benefisyè.') }}</p>
                </div>
                <span class="w-8 h-8 rounded-full bg-primary-fixed text-primary-container flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">calendar_month</span></span>
            </div>

            <div class="space-y-space-sm">
                @foreach ($cycles as $cycle)
                    @php
                        $paid = $mine->has($cycle->id);
                        $isMe = $cycle->beneficiary_member_id === $member->id;
                        $isOpen = $cycle->status === 'open';
                        $badge = match (true) {
                            $paid => ['badge-paid', __('Peye')],
                            $isOpen && $cycle->isLate() => ['badge-late', __('An reta')],
                            $isOpen => ['badge-wait', __('Pou peye')],
                            $cycle->status === 'closed' => ['badge-muted', __('Pa peye')],
                            default => ['badge-muted', __('Ap vini')],
                        };
                    @endphp
                    <div class="p-space-sm rounded-lg space-y-1.5 {{ $isMe ? 'bg-primary-fixed/50' : ($isOpen ? 'bg-amber-50/80' : 'bg-surface-container-low') }}" data-cycle="{{ $cycle->cycle_number }}">
                        <div class="flex items-center justify-between gap-space-sm">
                            <div class="flex items-center gap-space-sm min-w-0">
                                <span class="w-8 h-8 rounded-full font-label-sm text-label-sm flex items-center justify-center shrink-0
                                             {{ $isMe ? 'bg-primary-container text-on-primary' : ($isOpen ? 'bg-amber-200 text-amber-900' : 'bg-surface-container text-on-surface-variant') }}">{{ $cycle->cycle_number }}</span>
                                <div class="min-w-0">
                                    <p class="font-body-xs text-body-xs text-on-surface-variant">{{ __('Benefisyè tou sa a') }}</p>
                                    <p class="font-title-md text-title-md truncate {{ $isMe ? 'text-primary-container font-bold' : '' }}">
                                        {{ $cycle->beneficiary?->name ?? '—' }}@if ($isMe) ({{ __('Ou') }}) @endif
                                    </p>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">{{ $cycle->deadline->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <span class="{{ $badge[0] }} shrink-0">{{ $badge[1] }}</span>
                        </div>
                        @if ($isMe)
                            <p class="flex items-center gap-1.5 font-label-md text-label-md text-primary-container bg-surface-container-lowest/80 px-2.5 py-1 rounded-md tabular-nums">
                                <span class="material-symbols-outlined text-[16px] text-tertiary-container">savings</span>
                                {{ __('Ou se benefisyè tou sa a! W ap touche') }} {{ $fmt($tontine->netPotAmount()) }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>

            <p class="font-body-sm text-body-sm text-on-surface-variant text-center tabular-nums">{{ __('Total ou peye') }} : {{ $fmt($totalPaid) }}</p>
        </section>
    @endif
</x-portal-layout>
