<x-app-layout>
    <x-page-header :back="route('tontines.show', $tontine)" :title="__('Pòtay manm yo')" :subtitle="$tontine->name" />

    <div class="space-y-space-md">
        @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif

        <form method="POST" action="{{ route('tontines.portal.update', $tontine) }}" enctype="multipart/form-data" class="card space-y-space-md">
            @csrf @method('PUT')

            <label class="flex items-start gap-3">
                <input type="checkbox" name="member_portal_enabled" value="1" class="mt-1 rounded border-slate-300 text-primary-container"
                       @checked(old('member_portal_enabled', $tontine->member_portal_enabled))>
                <span>
                    <span class="font-title-md text-title-md block">{{ __('Ouvri yon espas pou manm yo') }}</span>
                    <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Chak manm resevwa yon lyen pèsonèl pou wè kalandriye a ak peman li yo. Pòtay la ap rete louvri jiskaske tout benefisyè yo konfime yo resevwa lajan yo.') }}</span>
                </span>
            </label>
            <x-input-error :messages="$errors->get('member_portal_enabled')" />

            <div class="space-y-space-sm">
                <p class="font-title-md text-title-md">{{ __('Kote manm yo voye lajan an') }}</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Ou menm ou jwenn lajan an dirèkteman. Lajan Ansanm pa jwenn lajan an.') }}</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-md">
                    <div class="card space-y-space-sm p-space-sm">
                        <h3 class="font-title-sm text-title-sm font-semibold text-on-surface">
                            <x-payment-method-logo method="moncash" size="h-6 w-6" /> MonCash
                        </h3>
                        <div>
                            <label class="label flex items-center gap-2" for="moncash_number">{{ __('nimewo') }}</label>
                            <input id="moncash_number" name="moncash_number" class="input" value="{{ old('moncash_number', $payment['moncash_number']) }}" placeholder="+509 ...">
                            <x-input-error :messages="$errors->get('moncash_number')" class="mt-1" />
                        </div>
                        <div>
                            <label class="label" for="moncash_name">{{ __('non sou kont lan') }}</label>
                            <input id="moncash_name" name="moncash_name" class="input" value="{{ old('moncash_name', $payment['moncash_name']) }}">
                        </div>
                        <div class="space-y-2">
                            <label class="label" for="moncash_qr">{{ __('QR MonCash (obligatwa pou aktive metòd la)') }}</label>
                            <input id="moncash_qr" name="moncash_qr" type="file" accept="image/jpeg,image/png,image/webp" class="input">
                            <x-input-error :messages="$errors->get('moncash_qr')" class="mt-1" />
                            @if ($payment['moncash_qr_path'] && \Illuminate\Support\Facades\Storage::disk('local')->exists($payment['moncash_qr_path']))
                                <img src="{{ route('tontines.payment-qr', [$tontine, 'moncash']) }}" alt="{{ __('QR MonCash') }}" class="h-40 w-40 rounded-lg border object-contain">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_moncash_qr" value="1"> {{ __('Retire QR MonCash la') }}</label>
                            @endif
                        </div>
                    </div>
                    <div class="card space-y-space-sm p-space-sm">
                        <h3 class="font-title-sm text-title-sm font-semibold text-on-surface">
                            <x-payment-method-logo method="natcash" size="h-6 w-6" /> NatCash
                        </h3>
                        <div>
                            <label class="label flex items-center gap-2" for="natcash_number">{{ __('nimewo') }}</label>
                            <input id="natcash_number" name="natcash_number" class="input" value="{{ old('natcash_number', $payment['natcash_number']) }}" placeholder="+509 ...">
                            <x-input-error :messages="$errors->get('natcash_number')" class="mt-1" />
                        </div>
                        <div>
                            <label class="label" for="natcash_name">{{ __('non sou kont lan') }}</label>
                            <input id="natcash_name" name="natcash_name" class="input" value="{{ old('natcash_name', $payment['natcash_name']) }}">
                        </div>
                        <div class="space-y-2">
                            <label class="label" for="natcash_qr">{{ __('QR NatCash (obligatwa pou aktive metòd la)') }}</label>
                            <input id="natcash_qr" name="natcash_qr" type="file" accept="image/jpeg,image/png,image/webp" class="input">
                            <x-input-error :messages="$errors->get('natcash_qr')" class="mt-1" />
                            @if ($payment['natcash_qr_path'] && \Illuminate\Support\Facades\Storage::disk('local')->exists($payment['natcash_qr_path']))
                                <img src="{{ route('tontines.payment-qr', [$tontine, 'natcash']) }}" alt="{{ __('QR NatCash') }}" class="h-40 w-40 rounded-lg border object-contain">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_natcash_qr" value="1"> {{ __('Retire QR NatCash la') }}</label>
                            @endif
                        </div>
                    </div>
                </div>
                <div>
                    <label class="label" for="bank_info">{{ __('Labank (non labank, nimewo kont, non)') }}</label>
                    <textarea id="bank_info" name="bank_info" rows="3" class="input">{{ old('bank_info', $payment['bank_info']) }}</textarea>
                    <x-input-error :messages="$errors->get('bank_info')" class="mt-1" />
                </div>
                <div>
                    <label class="label" for="instructions">{{ __('Enstriksyon pou manm yo (opsyonèl)') }}</label>
                    <textarea id="instructions" name="instructions" rows="2" class="input">{{ old('instructions', $payment['instructions']) }}</textarea>
                </div>
            </div>

            <button class="btn-primary w-full"><span class="material-symbols-outlined">save</span> {{ __('Sove') }}</button>
        </form>

        @if ($tontine->member_portal_enabled)
            <div class="space-y-space-sm" data-links>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-title-md text-title-md">{{ __('Lyen manm yo') }}</h2>
                    @if ($members->contains(fn ($m) => ! $m->access_token_hash))
                        <form method="POST" action="{{ route('tontines.portal.links', $tontine) }}">@csrf
                            <button class="btn-secondary btn-sm">{{ __('Kreye lyen ki manke yo') }}</button>
                        </form>
                    @endif
                </div>

                @foreach ($members as $m)
                    @php
                        $url = $m->portalUrl();
                        $wa = $m->whatsappNumber();
                        $text = __('Bonjou :name, men lyen pèsonèl ou pou swiv sòl :tontine', ['name' => $m->name, 'tontine' => $tontine->name]).' : '.$url;
                    @endphp
                    <div class="card !p-space-sm flex items-center gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="font-title-md text-title-md truncate">{{ $m->name }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                @if ($m->last_portal_visit_at) {{ __('Dènye vizit') }} : {{ $m->last_portal_visit_at->format('d/m/Y') }} @else {{ __('Poko louvri lyen an') }} @endif
                            </p>
                        </div>
                        @if ($url)
                            <button type="button" x-data="{ ok: false }" @click="navigator.clipboard.writeText(@js($url)); ok = true; setTimeout(() => ok = false, 1500)"
                                    class="w-10 h-10 flex items-center justify-center rounded-full bg-surface-container-low" title="{{ __('Kopye lyen an') }}">
                                <span class="material-symbols-outlined" x-text="ok ? 'check' : 'content_copy'">content_copy</span>
                            </button>
                            <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode($text) }}" target="_blank" rel="noopener"
                               class="w-10 h-10 flex items-center justify-center rounded-full bg-green-100 text-green-700" title="WhatsApp">
                                <span class="material-symbols-outlined">chat</span>
                            </a>
                            <form method="POST" action="{{ route('tontines.portal.link', [$tontine, $m]) }}" onsubmit="return confirm('{{ __('Kreye yon nouvo lyen? Ansyen lyen an p ap mache ankò.') }}')">@csrf
                                <button class="w-10 h-10 flex items-center justify-center rounded-full bg-surface-container-low" title="{{ __('Renouvle lyen an') }}">
                                    <span class="material-symbols-outlined">autorenew</span>
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
