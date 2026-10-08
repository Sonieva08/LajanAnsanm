<x-admin-layout active="plans" :title="$plan->exists ? __('Modifye plan') : __('Nouvo plan')">
    @php
        $cur = config('lajanansanm.currency');
        $unlimT = old('_token') ? (bool) old('unlimited_tontines') : ($plan->exists && $plan->max_active_tontines === null);
        $unlimM = old('_token') ? (bool) old('unlimited_members') : ($plan->exists && $plan->max_members_per_tontine === null);
        $selected = old('_token') ? (array) old('features', []) : ($plan->features ?? []);
        $val = fn ($k, $d = null) => old($k, $plan->{$k} ?? $d);
        $action = $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store');
    @endphp

    <div class="mb-space-lg flex items-center gap-3">
        <a href="{{ route('admin.plans') }}" class="w-10 h-10 rounded-full bg-surface-container-lowest shadow-sm flex items-center justify-center"><span class="material-symbols-outlined">arrow_back</span></a>
        <div>
            <h1 class="font-headline-lg text-headline-lg">{{ $plan->exists ? __('Modifye plan') . ' : ' . $plan->name : __('Nouvo plan') }}</h1>
            @if ($plan->exists && ($subscribers ?? 0) > 0)
                <p class="font-body-sm text-body-sm text-amber-700">⚠ {{ $subscribers }} {{ __('abòne aktif. Chanjman limit ak fonksyon yo aplike a yo imedyatman. Pri a sèlman pou nouvo abònman.') }}</p>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ $action }}" class="grid lg:grid-cols-12 gap-space-md items-start"
          x-data="{ name: @js($val('name', '')), desc: @js($val('description', '')), badge: @js($val('badge', '')),
                    pm: @js((string) $val('price_monthly', '')), pq: @js((string) $val('price_quarterly', '')), py: @js((string) $val('price_yearly', '')),
                    ut: {{ $unlimT ? 'true' : 'false' }}, um: {{ $unlimM ? 'true' : 'false' }}, mt: @js((string) $val('max_active_tontines', '')), mm: @js((string) $val('max_members_per_tontine', '')),
                    feats: @js(array_values($selected)), all: @js(collect(\App\Support\Features::ALL)->map(fn ($f) => __($f[0]))->all()) }">
        @csrf
        @if ($plan->exists) @method('PUT') @endif

        <div class="lg:col-span-7 space-y-space-md">
            @if ($errors->any())<div class="alert-err">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

            <section class="card !p-space-lg space-y-space-md">
                <h2 class="font-title-lg text-title-lg">1. {{ __('Enfòmasyon debaz') }}</h2>
                <div class="grid sm:grid-cols-2 gap-space-md">
                    <div><label class="label">{{ __('Non plan an') }}</label><input name="name" class="input" x-model="name" required maxlength="60"></div>
                    <div><label class="label">{{ __('Tèks badj (opsyonèl)') }}</label><input name="badge" class="input" x-model="badge" maxlength="30" placeholder="{{ __('Pi popilè') }}"></div>
                </div>
                <div><label class="label">{{ __('Deskripsyon') }}</label><textarea name="description" rows="2" maxlength="200" class="input !min-h-0" x-model="desc"></textarea></div>
                <div class="grid sm:grid-cols-2 gap-space-md">
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_highlighted" value="1" class="rounded border-slate-300 text-primary-container" @checked($val('is_highlighted', false))> {{ __('Mete plan sa a an vedèt') }}</label>
                    <div><label class="label">{{ __('Lòd afichaj') }}</label><input type="number" name="sort_order" min="0" max="999" class="input" value="{{ $val('sort_order', 0) }}"></div>
                </div>
                @unless ($plan->is_free)
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-primary-container" @checked($val('is_active', false))> {{ __('Plan aktif (vizib pou kliyan yo)') }}</label>
                @endunless
            </section>

            @unless ($plan->is_free)
                <section class="card !p-space-lg space-y-space-md">
                    <h2 class="font-title-lg text-title-lg">2. {{ __('Tarif pa peryòd') }} ({{ $cur }})</h2>
                    <div class="grid sm:grid-cols-3 gap-space-md">
                        <div><label class="label">{{ __('Pri pa mwa') }}</label><input type="number" step="0.01" min="0" name="price_monthly" class="input" x-model="pm"></div>
                        <div><label class="label">{{ __('Pri pou 3 mwa') }}</label><input type="number" step="0.01" min="0" name="price_quarterly" class="input" x-model="pq">
                            <p class="font-body-sm text-body-sm text-emerald-700 mt-1" x-show="pm && pq && pq < pm * 3" x-text="'−' + Math.round((1 - pq / (pm * 3)) * 100) + '%'"></p></div>
                        <div><label class="label">{{ __('Pri pou 12 mwa') }}</label><input type="number" step="0.01" min="0" name="price_yearly" class="input" x-model="py">
                            <p class="font-body-sm text-body-sm text-emerald-700 mt-1" x-show="pm && py && py < pm * 12" x-text="'−' + Math.round((1 - py / (pm * 12)) * 100) + '%'"></p></div>
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Kite yon peryòd vid si ou pa vle ofri li.') }}</p>
                </section>
            @endunless

            <section class="card !p-space-lg space-y-space-md">
                <h2 class="font-title-lg text-title-lg">3. {{ __('Limit') }}</h2>
                <div class="grid sm:grid-cols-2 gap-space-md">
                    <div>
                        <label class="label">{{ __('Tontin aktif maksimòm') }}</label>
                        <input type="number" min="1" name="max_active_tontines" class="input" x-model="mt" :disabled="ut">
                        <label class="flex items-center gap-2 mt-2"><input type="checkbox" name="unlimited_tontines" value="1" x-model="ut" class="rounded border-slate-300 text-primary-container"> {{ __('Ilimite') }}</label>
                    </div>
                    <div>
                        <label class="label">{{ __('Manm maksimòm pa tontin') }}</label>
                        <input type="number" min="2" name="max_members_per_tontine" class="input" x-model="mm" :disabled="um">
                        <label class="flex items-center gap-2 mt-2"><input type="checkbox" name="unlimited_members" value="1" x-model="um" class="rounded border-slate-300 text-primary-container"> {{ __('Ilimite') }}</label>
                    </div>
                </div>
            </section>

            <section class="card !p-space-lg space-y-space-md">
                <h2 class="font-title-lg text-title-lg">4. {{ __('Fonksyon enkli') }}</h2>
                <div class="grid sm:grid-cols-2 gap-space-sm">
                    @foreach (\App\Support\Features::ALL as $key => [$label, $desc, $icon])
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-space-sm cursor-pointer">
                            <input type="checkbox" name="features[]" value="{{ $key }}" x-model="feats" class="mt-1 rounded border-slate-300 text-primary-container">
                            <span><span class="font-label-lg text-label-lg block">{{ __($label) }}</span><span class="font-body-sm text-body-sm text-on-surface-variant">{{ __($desc) }}</span></span>
                        </label>
                    @endforeach
                </div>
            </section>

            <div class="flex gap-3">
                <button class="btn-primary"><span class="material-symbols-outlined">save</span> {{ __('Sove chanjman plan an') }}</button>
                <a href="{{ route('admin.plans') }}" class="btn-secondary">{{ __('Anile') }}</a>
            </div>
        </div>

        {{-- Aperçu --}}
        <div class="lg:col-span-5 space-y-space-sm lg:sticky lg:top-20">
            <p class="font-title-md text-title-md flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">visibility</span> {{ __('Aperi paj piblik la') }}</p>
            <div class="card !p-space-lg space-y-space-md shadow-md">
                <span x-show="badge" class="badge bg-primary-container text-on-primary" x-text="badge"></span>
                <h3 class="font-title-lg text-title-lg" x-text="name || '—'"></h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="desc"></p>
                <p class="font-currency-display text-currency-display tabular-nums"><span x-text="pm ? Number(pm).toLocaleString('en-US') : '—'"></span> <span class="font-title-md text-title-md text-on-surface-variant">{{ $cur }} / {{ __('mwa') }}</span></p>
                <ul class="space-y-1.5 font-body-md text-body-md">
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px] text-emerald-500">check_circle</span><span x-text="ut ? '{{ __('Tontin ilimite') }}' : (mt || '?') + ' {{ __('tontin aktif') }}'"></span></li>
                    <li class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px] text-emerald-500">check_circle</span><span x-text="'{{ __('Jiska') }} ' + (um ? '∞' : (mm || '?')) + ' {{ __('manm pa tontin') }}'"></span></li>
                    <template x-for="(label, key) in all" :key="key">
                        <li class="flex items-center gap-2" :class="feats.includes(key) ? '' : 'text-slate-400 line-through'">
                            <span class="material-symbols-outlined text-[18px]" :class="feats.includes(key) ? 'text-emerald-500' : ''" x-text="feats.includes(key) ? 'check_circle' : 'remove_circle'"></span><span x-text="label"></span>
                        </li>
                    </template>
                </ul>
            </div>
            @if ($plan->exists && ! $plan->is_free)
                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" onsubmit="return confirm('{{ __('Efase plan sa a?') }}')">@csrf @method('DELETE')
                    <button class="font-label-md text-label-md text-secondary underline">{{ __('Efase plan sa a') }}</button>
                </form>
            @endif
        </div>
    </form>
</x-admin-layout>
