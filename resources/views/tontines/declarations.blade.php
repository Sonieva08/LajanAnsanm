<x-app-layout>
    @php
        $cur = config('lajanansanm.currency');
        $fmt = fn ($n) => number_format((float) $n, 0, '.', ',').' '.$cur;
        $when = function ($dt) {
            return $dt->isToday() ? __('Jodi a').', '.$dt->format('H:i')
                : ($dt->isYesterday() ? __('Yè').', '.$dt->format('H:i') : $dt->format('d/m/Y'));
        };
        $icons = ['moncash' => 'phone_android', 'natcash' => 'account_balance_wallet', 'bank' => 'account_balance'];
        $tones = ['moncash' => 'bg-red-100 text-red-700', 'natcash' => 'bg-orange-100 text-orange-700', 'bank' => 'bg-primary-fixed text-primary-container'];
    @endphp

    @include('partials.copy-toast')

    <x-page-header :back="route('tontines.show', $tontine)" :title="__('Peman manm yo deklare')" :subtitle="$tontine->name">
        @if ($pending->isNotEmpty())
            <span class="badge-wait shrink-0"><span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>{{ $pending->count() }} {{ __('ap tann') }}</span>
        @endif
    </x-page-header>

    <div class="space-y-space-md">
        @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->has('declaration'))<div class="alert-err">{{ $errors->first('declaration') }}</div>@endif
        @if ($errors->has('review_note'))<div class="alert-err">{{ $errors->first('review_note') }}</div>@endif

        {{-- Prudence --}}
        <div class="bg-surface-container rounded-xl p-space-md flex items-start gap-space-sm">
            <span class="p-2 rounded-lg bg-primary-fixed text-primary-container shrink-0"><span class="material-symbols-outlined text-[20px]">verified_user</span></span>
            <div class="min-w-0">
                <p class="font-label-lg text-label-lg text-primary-container font-semibold">{{ __('Prekosyon verifikasyon') }}</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">{{ __('Toujou tcheke aplikasyon MonCash, NatCash oswa bous labank ou avan ou valide peman an.') }}</p>
            </div>
        </div>

        {{-- En attente --}}
        <div class="space-y-space-sm">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-space-xs font-title-md text-title-md"><span class="material-symbols-outlined text-[20px]">pending_actions</span>{{ __('Deklarasyon ki ap tann') }}</h2>
                <span class="font-label-md text-label-md text-on-surface-variant tabular-nums">{{ $pending->count() }} {{ __('sou') }} {{ $tontine->activeMembersCount() }} {{ __('manm') }}</span>
            </div>

            @forelse ($pending as $d)
                <div class="card space-y-space-md" data-declaration="{{ $d->id }}" x-data="{ reject: false }">
                    <div class="flex items-start justify-between gap-space-xs">
                        <div class="flex items-center gap-space-sm min-w-0">
                            <x-avatar :name="$d->member->name" />
                            <div class="min-w-0">
                                <p class="font-title-md text-title-md truncate">{{ $d->member->name }}</p>
                                <p class="flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant tabular-nums">
                                    <span class="material-symbols-outlined text-[15px]">event_repeat</span>{{ __('Tou') }} {{ $d->cycle->cycle_number }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-headline-md text-headline-md text-primary-container font-bold tabular-nums">{{ $fmt($d->amount) }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $when($d->created_at) }}</p>
                        </div>
                    </div>

                    <div class="bg-surface-container-low rounded-lg p-3 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Metòd') }}</span>
                            <span class="badge {{ $tones[$d->payment_method] ?? 'bg-slate-100 text-slate-600' }}">
                                @if ($d->payment_method === 'moncash' || $d->payment_method === 'natcash')
                                    <x-payment-method-logo :method="$d->payment_method" size="h-5 w-5" />
                                @else
                                    <span class="material-symbols-outlined text-[13px]">{{ $icons[$d->payment_method] ?? 'payments' }}</span>
                                @endif
                                {{ $d->methodLabel() }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Nimewo li peye ak li') }}</span>
                            <button type="button" class="inline-flex items-center gap-1 font-label-lg text-label-lg font-semibold active:opacity-60 tabular-nums"
                                    x-on:click="copyText(@js($d->payer_phone), @js(__('Nimewo kopye!')))">
                                {{ $d->payer_phone }}<span class="material-symbols-outlined text-[16px] text-primary-container">content_copy</span>
                            </button>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Nimewo tranzaksyon') }}</span>
                            <button type="button" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container font-bold tracking-wider active:opacity-60 tabular-nums break-all text-right"
                                    x-on:click="copyText(@js($d->transaction_ref), @js(__('Nimewo tranzaksyon kopye!')))">
                                {{ $d->transaction_ref }}<span class="material-symbols-outlined text-[16px]">content_copy</span>
                            </button>
                        </div>
                        @if ($d->note)
                            <div class="pt-1.5 flex items-start gap-1.5 text-on-surface-variant">
                                <span class="material-symbols-outlined text-[16px] mt-0.5 shrink-0">chat_bubble</span>
                                <p class="font-body-sm text-body-sm italic">{{ $d->note }}</p>
                            </div>
                        @endif
                        @if ($d->hasReceipt())
                            <div class="flex items-center justify-between gap-2 pt-2 border-t border-surface-container">
                                <span class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[18px] text-primary-container">receipt_long</span>{{ __('Prèv peman (foto / resi)') }}
                                </span>
                                <a href="{{ route('tontines.declarations.receipt', [$tontine, $d]) }}" target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-container text-on-primary font-label-md text-label-md font-semibold active:opacity-80 transition shadow-sm">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    {{ __('Gade resi a') }}
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-space-sm" x-show="!reject">
                        <button type="button" @click="reject = true" class="h-12 rounded-lg bg-surface-container-high text-error font-label-lg text-label-lg font-bold flex items-center justify-center gap-1 active:bg-error-container transition">
                            <span class="material-symbols-outlined text-[20px]">cancel</span>{{ __('Rejte') }}
                        </button>
                        <form method="POST" action="{{ route('tontines.declarations.approve', [$tontine, $d]) }}">@csrf
                            <button class="h-12 w-full rounded-lg bg-tertiary-container text-tertiary-fixed font-label-lg text-label-lg font-bold shadow-sm flex items-center justify-center gap-1 active:opacity-90 transition">
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>{{ __('Valide') }}
                            </button>
                        </form>
                    </div>

                    <form x-show="reject" x-cloak method="POST" action="{{ route('tontines.declarations.reject', [$tontine, $d]) }}" class="bg-error-container/40 rounded-xl p-space-sm space-y-space-xs">
                        @csrf
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-1.5 font-label-lg text-label-lg font-bold text-on-error-container" for="note-{{ $d->id }}">
                                <span class="material-symbols-outlined text-[18px]">announcement</span>{{ __('Poukisa ou rejte li?') }}
                            </label>
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Obligatwa') }}</span>
                        </div>
                        <textarea id="note-{{ $d->id }}" name="review_note" rows="2" maxlength="300" required
                                  class="w-full rounded-lg border-0 bg-surface-container-lowest p-2.5 font-body-md text-body-md focus:ring-2 focus:ring-primary-container resize-none"
                                  placeholder="{{ __('Esplike sa ki pa kòrèk la bay') }} {{ $d->member->name }}..."></textarea>
                        <div class="flex items-center gap-space-sm pt-1">
                            <button type="button" @click="reject = false" class="h-11 px-4 rounded-lg bg-surface-container-high font-label-lg text-label-lg font-semibold">{{ __('Anile') }}</button>
                            <button class="h-11 flex-1 rounded-lg bg-error text-on-error font-label-lg text-label-lg font-bold flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-[18px]">send</span>{{ __('Konfime rejè a') }}
                            </button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="card !p-space-lg text-center space-y-3">
                    <span class="mx-auto w-14 h-14 rounded-full bg-tertiary-fixed/30 flex items-center justify-center text-on-tertiary-container"><span class="material-symbols-outlined text-[32px]">task_alt</span></span>
                    <div class="space-y-1">
                        <p class="font-title-md text-title-md font-bold">{{ __('Pa gen okenn deklarasyon ki ap tann') }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant max-w-xs mx-auto">{{ __('Lè yon manm deklare yon peman, li parèt isit la pou ou valide li.') }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Historique --}}
        @if ($recent->isNotEmpty())
            <div class="space-y-space-sm pt-space-sm">
                <h2 class="flex items-center gap-space-xs font-title-md text-title-md"><span class="material-symbols-outlined text-[20px]">history_toggle_off</span>{{ __('Dènye deklarasyon trete yo') }}</h2>
                <div class="card !p-0 overflow-hidden divide-y divide-surface-container">
                    @foreach ($recent as $d)
                        <div class="p-3.5 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-label-lg text-label-lg font-semibold truncate">{{ $d->member->name }}</p>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">{{ __('Tou') }} {{ $d->cycle->cycle_number }} · {{ $d->methodLabel() }} · {{ $when($d->reviewed_at ?? $d->updated_at) }}</p>
                                </div>
                                <div class="text-right shrink-0 space-y-1">
                                    <p class="font-label-lg text-label-lg font-bold tabular-nums">{{ $fmt($d->amount) }}</p>
                                    <span class="{{ $d->status === 'approved' ? 'badge-paid' : 'badge-late' }}">{{ $d->status === 'approved' ? __('Valide') : __('Rejte') }}</span>
                                </div>
                            </div>
                            @if ($d->status === 'approved')
                                <p class="font-body-sm text-body-sm text-on-surface-variant bg-surface-container-low px-2 py-1 rounded tabular-nums">{{ __('Ref') }}: {{ $d->transaction_ref }}</p>
                            @elseif ($d->review_note)
                                <p class="font-body-sm text-body-sm text-error bg-error-container/30 px-2 py-1 rounded">{{ __('Rezon') }}: {{ $d->review_note }}</p>
                            @endif
                            @if ($d->hasReceipt())
                                <p class="pt-0.5">
                                    <a href="{{ route('tontines.declarations.receipt', [$tontine, $d]) }}" target="_blank"
                                       class="inline-flex items-center gap-1 font-label-sm text-label-sm text-primary-container font-semibold hover:underline">
                                        <span class="material-symbols-outlined text-[15px]">receipt_long</span>{{ __('Gade resi a') }}
                                    </a>
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
