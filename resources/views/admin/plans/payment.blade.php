<x-admin-layout active="plans" :title="__('Kote kliyan yo voye peman an')">
    @php
        $cur = config('lajanansanm.currency');
        $cfg = [
            'moncash' => filled($payment['moncash_qr_path']) && \Illuminate\Support\Facades\Storage::disk('local')->exists($payment['moncash_qr_path']),
            'natcash' => filled($payment['natcash_qr_path']) && \Illuminate\Support\Facades\Storage::disk('local')->exists($payment['natcash_qr_path']),
            'bank' => filled($payment['bank_info']),
        ];
        $any = in_array(true, $cfg, true);
        $at = $meta['at'] ? \Illuminate\Support\Carbon::parse($meta['at'])->timezone(config('app.timezone')) : null;
        $v = fn ($k) => old($k, $payment[$k] ?? '');
    @endphp

    <div class="mb-space-lg space-y-space-sm">
        <nav class="flex items-center gap-1 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
            <span>{{ __('Konsòl pwopriyetè') }}</span><span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <a href="{{ route('admin.plans') }}" class="hover:text-primary">{{ __('Plan ak abònman') }}</a><span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <span class="text-primary font-bold">{{ __('Konfigirasyon peman') }}</span>
        </nav>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-headline-lg text-headline-lg text-primary">{{ __('Plan ak abònman') }}</h1>
                <p class="text-on-surface-variant max-w-2xl">{{ __('Jere kote kliyan yo voye peman abònman an ak règ ekspirasyon yo.') }}</p>
            </div>
            <a href="{{ route('admin.requests') }}" class="btn-danger">
                <span class="material-symbols-outlined">mark_email_unread</span> {{ __('Wè demann peman') }}
                @if ($stats['pending'] > 0)<span class="bg-white text-secondary rounded-full px-2 text-[12px] font-bold">{{ $stats['pending'] }}</span>@endif
            </a>
        </div>
        <div class="flex items-center gap-1 bg-surface-container-low p-1.5 rounded-xl overflow-x-auto">
            <a href="{{ route('admin.plans') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-lowest font-label-md text-label-md whitespace-nowrap"><span class="material-symbols-outlined text-[18px]">tune</span> {{ __('Lis plan yo') }}</a>
            <span class="flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-container-lowest text-primary font-bold shadow-sm font-label-md text-label-md whitespace-nowrap"><span class="material-symbols-outlined text-[18px]">account_balance_wallet</span> {{ __('Kote kliyan yo voye peman an ak règ') }}</span>
            <span class="flex items-center gap-2 px-4 py-2 rounded-lg text-slate-400 font-label-md text-label-md whitespace-nowrap cursor-not-allowed"><span class="material-symbols-outlined text-[18px]">history</span> {{ __('Istorik chanjman') }} <span class="badge-muted !py-0 !px-2 text-[10px]">{{ __('Byento') }}</span></span>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.plans.rules') }}" enctype="multipart/form-data" class="card !p-0 overflow-hidden"
          x-data="{ grace: {{ (int) old('grace_days', $rules['grace_days']) }}, remind: {{ (int) old('remind_days', $rules['remind_days']) }},
                    mc: @js($v('moncash_number')), mcn: @js($v('moncash_name')), nc: @js($v('natcash_number')), ncn: @js($v('natcash_name')), bank: @js($v('bank_info')) }">
        @csrf

        <div class="p-space-lg bg-gradient-to-r from-surface-container-low via-surface-container-lowest to-surface-container-low/40 space-y-space-md">
            <div class="flex items-start gap-space-md">
                <div class="w-12 h-12 rounded-xl bg-primary-container text-on-primary flex items-center justify-center shrink-0 shadow-sm"><span class="material-symbols-outlined text-[26px]" style="font-variation-settings: 'FILL' 1;">payments</span></div>
                <div>
                    <h2 class="font-headline-md text-headline-md text-primary">{{ __('Kote kliyan yo voye peman an') }}</h2>
                    <p class="text-on-surface-variant">{{ __('Enfòmasyon sa yo parèt sou paj « Mande abònman » a, pou kliyan yo ka peye pa MonCash, NatCash oswa labank.') }}</p>
                </div>
            </div>

            @if (! $any)
                <div class="p-space-md rounded-xl bg-secondary-fixed text-on-secondary-fixed flex items-start gap-3" data-no-method>
                    <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">warning</span>
                    <div>
                        <p class="font-title-md text-title-md font-bold">{{ __('Si ou pa ranpli okenn metòd, pa gen moun ki ka mande abònman.') }}</p>
                        <p class="font-body-sm text-body-sm">{{ __('Telechaje yon QR MonCash oswa NatCash, oswa antre enfòmasyon bank.') }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="p-space-lg space-y-space-lg">
            @if ($errors->any())<div class="alert-err">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

            <div class="grid lg:grid-cols-2 gap-space-md">
                {{-- MonCash --}}
                <div class="card relative overflow-hidden space-y-space-md">
                    <div class="absolute top-0 inset-x-0 h-1 bg-secondary-container"></div>
                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-3">
                            <x-payment-method-logo method="moncash" />
                            <span class="font-title-md text-title-md font-bold">MonCash</span>
                        </div>
                        <span class="{{ $cfg['moncash'] ? 'badge-paid' : 'badge-muted' }}">{{ $cfg['moncash'] ? __('Konfigire') : __('Pa konfigire') }}</span>
                    </div>
                    <div><label class="label">{{ __('Nimewo MonCash pwopriyetè') }}</label>
                        <div class="relative"><span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">phone_iphone</span>
                            <input name="moncash_number" x-model="mc" class="input pl-10 font-title-md text-title-md tabular-nums" placeholder="+509 ..."></div>
                        <x-input-error :messages="$errors->get('moncash_number')" class="mt-1" /></div>
                    <div><label class="label">{{ __('Non sou kont MonCash') }}</label>
                        <input name="moncash_name" x-model="mcn" class="input" maxlength="80"></div>
                    <div class="space-y-2">
                        <label class="label" for="moncash_qr">{{ __('QR MonCash (obligatwa pou aktive metòd la)') }}</label>
                        <input id="moncash_qr" name="moncash_qr" type="file" accept="image/jpeg,image/png,image/webp" class="input">
                        <x-input-error :messages="$errors->get('moncash_qr')" class="mt-1" />
                        @if ($cfg['moncash'])
                            <img src="{{ route('subscription.payment-qr', 'moncash') }}" alt="{{ __('QR MonCash') }}" class="h-40 w-40 rounded-lg border object-contain">
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_moncash_qr" value="1"> {{ __('Retire QR MonCash la') }}</label>
                        @endif
                    </div>
                </div>

                {{-- NatCash --}}
                <div class="card relative overflow-hidden space-y-space-md">
                    <div class="absolute top-0 inset-x-0 h-1 bg-primary-container"></div>
                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-3">
                            <x-payment-method-logo method="natcash" />
                            <span class="font-title-md text-title-md font-bold">NatCash</span>
                        </div>
                        <span class="{{ $cfg['natcash'] ? 'badge-paid' : 'badge-muted' }}">{{ $cfg['natcash'] ? __('Konfigire') : __('Pa konfigire') }}</span>
                    </div>
                    <div><label class="label">{{ __('Nimewo NatCash pwopriyetè') }}</label>
                        <div class="relative"><span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">phone_iphone</span>
                            <input name="natcash_number" x-model="nc" class="input pl-10 font-title-md text-title-md tabular-nums" placeholder="+509 ..."></div></div>
                    <div><label class="label">{{ __('Non sou kont NatCash') }}</label>
                        <input name="natcash_name" x-model="ncn" class="input" maxlength="80"></div>
                    <div class="space-y-2">
                        <label class="label" for="natcash_qr">{{ __('QR NatCash (obligatwa pou aktive metòd la)') }}</label>
                        <input id="natcash_qr" name="natcash_qr" type="file" accept="image/jpeg,image/png,image/webp" class="input">
                        <x-input-error :messages="$errors->get('natcash_qr')" class="mt-1" />
                        @if ($cfg['natcash'])
                            <img src="{{ route('subscription.payment-qr', 'natcash') }}" alt="{{ __('QR NatCash') }}" class="h-40 w-40 rounded-lg border object-contain">
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_natcash_qr" value="1"> {{ __('Retire QR NatCash la') }}</label>
                        @endif
                    </div>
                </div>

                {{-- Banque --}}
                <div class="card space-y-space-md">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-lg bg-surface-container text-primary flex items-center justify-center"><span class="material-symbols-outlined">account_balance</span></span>
                            <div><p class="font-title-md text-title-md font-bold">{{ __('Enfòmasyon kont labank') }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Depo dirèk oswa transfè') }}</p></div>
                        </div>
                        <span class="{{ $cfg['bank'] ? 'badge-paid' : 'badge-muted' }}">{{ $cfg['bank'] ? __('Konfigire') : __('Opsyonèl') }}</span>
                    </div>
                    <textarea name="bank_info" x-model="bank" rows="4" maxlength="300" class="input !min-h-0" placeholder="Bank: ...&#10;Non kont: ...&#10;Nimewo kont (HTG): ..."></textarea>
                </div>

                {{-- Instructions --}}
                <div class="card space-y-space-md">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-lg bg-surface-container text-primary flex items-center justify-center"><span class="material-symbols-outlined">contact_support</span></span>
                        <div><p class="font-title-md text-title-md font-bold">{{ __('Enstriksyon adisyonèl') }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Parèt sou fòm demann lan (opsyonèl)') }}</p></div>
                    </div>
                    <textarea name="instructions" rows="4" maxlength="400" class="input !min-h-0">{{ old('instructions', $payment['instructions']) }}</textarea>
                </div>
            </div>

            {{-- Règles --}}
            <div class="rounded-xl bg-surface-container-low/60 p-space-lg space-y-space-md">
                <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary">event_repeat</span><h3 class="font-title-lg text-title-lg text-primary font-bold">{{ __('Règ ekspirasyon') }}</h3></div>
                <p class="text-on-surface-variant">{{ __('Detèmine konbyen tan yon kliyan genyen apre abònman an fini anvan li tounen sou plan gratis la.') }}</p>

                <div class="grid md:grid-cols-2 gap-space-md">
                    <div class="card space-y-space-sm">
                        <div class="flex items-center justify-between"><label class="font-title-md text-title-md" for="grace_days">{{ __('Jou gras apre ekspirasyon') }}</label><span class="font-headline-md text-headline-md text-primary tabular-nums" x-text="grace + ' {{ __('jou') }}'"></span></div>
                        <div class="flex items-center gap-2">
                            <input id="grace_days" type="number" name="grace_days" min="0" max="60" x-model.number="grace" class="input font-headline-md text-headline-md text-primary" required>
                            <button type="button" @click="grace = Math.max(0, grace - 1)" class="w-12 h-12 shrink-0 rounded-xl bg-surface-container flex items-center justify-center"><span class="material-symbols-outlined">remove</span></button>
                            <button type="button" @click="grace = Math.min(60, grace + 1)" class="w-12 h-12 shrink-0 rounded-xl bg-surface-container flex items-center justify-center"><span class="material-symbols-outlined">add</span></button>
                        </div>
                        <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="'{{ __('Pandan') }} ' + grace + ' {{ __('jou sa yo, kliyan an kenbe tout avantaj plan an epi li wè yon banyè pou renouvle.') }}'"></p>
                        <x-input-error :messages="$errors->get('grace_days')" class="mt-1" />
                    </div>
                    <div class="card space-y-space-sm">
                        <div class="flex items-center justify-between"><label class="font-title-md text-title-md" for="remind_days">{{ __('Rapèl anvan ekspirasyon') }}</label><span class="font-headline-md text-headline-md text-primary tabular-nums" x-text="remind + ' {{ __('jou') }}'"></span></div>
                        <div class="flex items-center gap-2">
                            <input id="remind_days" type="number" name="remind_days" min="0" max="30" x-model.number="remind" class="input font-headline-md text-headline-md text-primary" required>
                            <button type="button" @click="remind = Math.max(0, remind - 1)" class="w-12 h-12 shrink-0 rounded-xl bg-surface-container flex items-center justify-center"><span class="material-symbols-outlined">remove</span></button>
                            <button type="button" @click="remind = Math.min(30, remind + 1)" class="w-12 h-12 shrink-0 rounded-xl bg-surface-container flex items-center justify-center"><span class="material-symbols-outlined">add</span></button>
                        </div>
                        <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="'{{ __('Yon banyè parèt sou paj kliyan an') }} ' + remind + ' {{ __('jou anvan dat ekspirasyon an.') }}'"></p>
                    </div>
                </div>

                <div class="flex items-start gap-2 bg-surface-container-lowest rounded-xl p-space-sm text-on-surface-variant">
                    <span class="material-symbols-outlined text-[20px] shrink-0">lock_clock</span>
                    <p class="font-body-sm text-body-sm">{{ __('Apre jou gras yo, kliyan an tounen sou plan gratis la. Sa ki depase limit gratis la pase an lekti sèlman jiskaske li renouvle ; anyen pa efase.') }}</p>
                </div>
            </div>

            {{-- Aperçu client --}}
            <div class="rounded-xl bg-surface-container-lowest border border-slate-200 p-space-md space-y-1">
                <p class="font-label-md text-label-md font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary text-[20px]">visibility</span> {{ __('Sa kliyan an ap wè sou paj demann lan') }}</p>
                <p class="font-body-md text-body-md" x-show="mc" x-cloak><strong>MonCash</strong> : <span class="tabular-nums" x-text="mc"></span> <span class="text-on-surface-variant" x-text="mcn ? '(' + mcn + ')' : ''"></span></p>
                <p class="font-body-md text-body-md" x-show="nc" x-cloak><strong>NatCash</strong> : <span class="tabular-nums" x-text="nc"></span> <span class="text-on-surface-variant" x-text="ncn ? '(' + ncn + ')' : ''"></span></p>
                <p class="font-body-md text-body-md whitespace-pre-line" x-show="bank" x-cloak x-text="bank"></p>
                <p class="font-body-sm text-body-sm text-on-surface-variant" x-show="!mc && !nc && !bank">{{ __('Okenn metòd pa konfigire.') }}</p>
            </div>
        </div>

        <div class="p-space-lg bg-surface-container-low flex flex-col sm:flex-row items-center justify-between gap-space-md">
            <p class="flex items-center gap-2 font-body-sm text-body-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-[18px]">update</span>
                @if ($meta['by'] && $at)
                    {{ __('Dènye fwa règ sa yo te modifye') }} : <strong class="text-on-surface">{{ $at->format('d/m/Y H:i') }}</strong> {{ __('pa') }} <strong class="text-on-surface">{{ $meta['by'] }}</strong>
                @else
                    {{ __('Poko gen modifikasyon.') }}
                @endif
            </p>
            <div class="flex items-center gap-space-sm">
                <a href="{{ route('admin.plans.payment') }}" class="px-5 py-3 rounded-xl bg-surface-container font-label-md text-label-md">{{ __('Anile chanjman yo') }}</a>
                <button class="btn-primary"><span class="material-symbols-outlined">save</span> {{ __('Sove règ yo') }}</button>
            </div>
        </div>
    </form>

    <div class="grid md:grid-cols-3 gap-space-md mt-space-lg">
        <div class="card flex items-center justify-between"><div><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Abònman aktif') }}</p><p class="font-headline-md text-headline-md text-primary tabular-nums">{{ number_format($stats['active']) }}</p></div><span class="material-symbols-outlined text-primary">groups</span></div>
        <div class="card flex items-center justify-between"><div><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Demann ki ap tann') }}</p><p class="font-headline-md text-headline-md text-secondary tabular-nums">{{ number_format($stats['pending']) }}</p></div><span class="material-symbols-outlined text-secondary">hourglass_top</span></div>
        <div class="card flex items-center justify-between"><div><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Revni mwa sa (peman konfime)') }}</p><p class="font-headline-md text-headline-md text-primary tabular-nums">{{ number_format($stats['revenue'], 0, '.', ',') }} {{ $cur }}</p></div><span class="material-symbols-outlined text-primary">trending_up</span></div>
    </div>
</x-admin-layout>
