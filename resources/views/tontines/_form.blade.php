@csrf
@php
    $locked = isset($tontine) && $tontine->isLocked();
    $pt = old('period_type', $tontine->period_type ?? 'weekly');
    $amount = old('amount_per_period', isset($tontine) ? (float) $tontine->amount_per_period : '');
    $pct = old('organizer_fee_percent', isset($tontine) ? (float) $tontine->organizer_fee_percent : 0);
    $start = old('start_date', isset($tontine) ? $tontine->start_date->format('Y-m-d') : now()->format('Y-m-d'));
    $cur = config('lajanansanm.currency');
    $canFee = auth()->user()?->is_owner || \App\Support\Entitlements::for(auth()->user())->has('organizer_fee');
@endphp

<div class="space-y-space-lg" x-data="{ amount: '{{ $amount }}', pct: '{{ $pct }}' }">
    @if ($locked)
        <div class="flex gap-2 bg-amber-50 text-amber-800 p-space-sm rounded-xl font-body-sm text-body-sm">
            <span class="material-symbols-outlined text-[18px]">lock</span>
            {{ __('Kalandriye a deja jenere: montan, frekans, dat ak kòmisyon an bloke. Se non an sèlman ou ka chanje.') }}
        </div>
    @endif

    <div>
        <label for="name" class="label">{{ __('Non tontine a') }}</label>
        <input id="name" name="name" type="text" class="input" required autofocus
               value="{{ old('name', $tontine->name ?? '') }}" placeholder="Sòl Fanmi ak Zanmi">
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <label for="amount_per_period" class="label">{{ __('Montan pou chak moun') }}</label>
        <div class="relative">
            <input id="amount_per_period" name="amount_per_period" type="number" step="0.01" min="1" required
                   x-model="amount" @disabled($locked) class="input pr-16 font-title-md text-title-md tabular-nums disabled:bg-slate-100">
            <span class="absolute inset-y-0 right-4 flex items-center font-label-md text-label-md text-on-surface-variant">{{ $cur }}</span>
        </div>
        @unless ($locked)
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach ([1000, 2500, 5000, 10000] as $v)
                    <button type="button" @click="amount = {{ $v }}"
                            :class="Number(amount) === {{ $v }} ? 'bg-primary-container text-on-primary' : 'bg-surface-container-high text-on-surface-variant'"
                            class="px-3 py-1.5 rounded-full font-label-md text-label-md transition tabular-nums">
                        {{ number_format($v, 0, '.', ',') }} {{ $cur }}
                    </button>
                @endforeach
            </div>
        @endunless
        <x-input-error :messages="$errors->get('amount_per_period')" class="mt-1" />
    </div>

    <div>
        <span class="label">{{ __('Frekans') }}</span>
        <div class="grid grid-cols-2 gap-1 bg-surface-container-high p-1 rounded-xl">
            @foreach (['weekly' => __('Chak semèn'), 'monthly' => __('Chak mwa')] as $v => $l)
                <label class="{{ $locked ? 'cursor-not-allowed opacity-70' : 'cursor-pointer' }}">
                    <input type="radio" name="period_type" value="{{ $v }}" class="peer sr-only" @checked($pt === $v) @disabled($locked)>
                    <span class="block text-center py-3 rounded-lg font-label-md text-label-md text-on-surface-variant peer-checked:bg-primary-container peer-checked:text-on-primary transition">{{ $l }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('period_type')" class="mt-1" />
    </div>

    <div>
        <label for="start_date" class="label">{{ __('Dat premye peman kòmanse') }}</label>
        <input id="start_date" name="start_date" type="date" class="input disabled:bg-slate-100" required value="{{ $start }}" @disabled($locked)>
        <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
    </div>

    @if ($canFee)
    {{-- Commission de l'organisateur --}}
    <div class="space-y-space-sm">
        <label for="organizer_fee_percent" class="label">{{ __('Kòmisyon òganizatè a (opsyonèl)') }}</label>
        <div class="relative">
            <input id="organizer_fee_percent" name="organizer_fee_percent" type="number" step="0.01" min="0"
                   max="{{ \App\Models\Tontine::MAX_FEE_PERCENT }}" x-model="pct" @disabled($locked)
                   class="input pr-12 font-title-md text-title-md tabular-nums disabled:bg-slate-100">
            <span class="absolute inset-y-0 right-4 flex items-center font-label-md text-label-md text-on-surface-variant">%</span>
        </div>
        @unless ($locked)
            <div class="flex flex-wrap gap-2">
                @foreach ([0, 5, 10, 15] as $v)
                    <button type="button" @click="pct = {{ $v }}"
                            :class="Number(pct) === {{ $v }} ? 'bg-primary-container text-on-primary' : 'bg-surface-container-high text-on-surface-variant'"
                            class="px-3 py-1.5 rounded-full font-label-md text-label-md transition">{{ $v === 0 ? __('Okenn') : $v . '%' }}</button>
                @endforeach
            </div>
        @endunless
        <p class="font-body-sm text-body-sm text-on-surface-variant">
            {{ __('Pousantaj sa a soustrè sou lajan benefisyè a resevwa a chak tou. Maksimòm') }} {{ \App\Models\Tontine::MAX_FEE_PERCENT }}%.
        </p>

        <div x-show="Number(pct) > 0 && Number(amount) > 0" x-cloak class="bg-primary-fixed/60 rounded-xl p-space-sm font-body-sm text-body-sm tabular-nums space-y-0.5">
            <p class="font-label-md text-label-md">{{ __('Egzanp ak 10 manm') }}</p>
            <div class="flex justify-between"><span>{{ __('Lajan ranmase') }}</span><span x-text="(Number(amount) * 10).toLocaleString('en-US') + ' {{ $cur }}'"></span></div>
            <div class="flex justify-between text-secondary"><span>{{ __('Kòmisyon òganizatè') }}</span><span x-text="'− ' + (Math.round(Number(amount) * 10 * Number(pct)) / 100).toLocaleString('en-US') + ' {{ $cur }}'"></span></div>
            <div class="flex justify-between font-bold border-t border-slate-300 pt-0.5"><span>{{ __('Benefisyè a resevwa') }}</span><span x-text="(Number(amount) * 10 - Math.round(Number(amount) * 10 * Number(pct)) / 100).toLocaleString('en-US') + ' {{ $cur }}'"></span></div>
        </div>
        <x-input-error :messages="$errors->get('organizer_fee_percent')" class="mt-1" />
    </div>
    @else
    <div class="rounded-xl bg-surface-container-low p-space-md flex items-start gap-3" data-fee-locked>
        <span class="material-symbols-outlined text-primary-container">lock</span>
        <div>
            <p class="font-label-lg text-label-lg">{{ __('Kòmisyon òganizatè a') }}</p>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Fonksyon sa a disponib sou yon plan peyan.') }}
                <a href="{{ route('plans') }}" class="underline text-primary-container">{{ __('Wè plan yo') }}</a></p>
        </div>
    </div>
    @endif

    <div class="flex gap-3 bg-primary-fixed/60 text-on-primary-fixed-variant p-space-md rounded-xl font-body-sm text-body-sm">
        <span class="material-symbols-outlined text-[20px]">info</span>
        <p>{{ __('Ou pral ajoute manm yo epi defini lòd benefisyè yo apre ou fin kreye tontin nan.') }}</p>
    </div>
</div>
