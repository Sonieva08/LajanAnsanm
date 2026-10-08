<x-admin-layout active="plans" :title="__('Plan ak abònman')">
    @php $cur = config('lajanansanm.currency'); @endphp

    <div class="flex flex-wrap items-start justify-between gap-4 mb-space-lg">
        <div>
            <h1 class="font-headline-lg text-headline-lg">{{ __('Jesyon plan ak abònman') }}</h1>
            <p class="text-on-surface-variant max-w-2xl">{{ __('Konfigire tarif yo, limit yo ak fonksyon yo. Pa gen kòd pou modifye.') }}</p>
        </div>
        <a href="{{ route('admin.plans.create') }}" class="btn-primary"><span class="material-symbols-outlined">add</span> {{ __('Nouvo plan') }}</a>
    </div>

    @if ($errors->has('plan'))<div class="alert-err mb-space-md">{{ $errors->first('plan') }}</div>@endif

    <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-space-md mb-space-lg">
        @foreach ($plans as $plan)
            <div class="card !p-space-md flex flex-col gap-space-sm {{ $plan->is_highlighted ? 'ring-2 ring-primary-container' : '' }}" data-plan-card="{{ $plan->slug }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        @if ($plan->badge)<span class="badge bg-primary-container text-on-primary mb-1">{{ $plan->badge }}</span>@endif
                        <h2 class="font-title-lg text-title-lg">{{ $plan->name }}</h2>
                    </div>
                    @if ($plan->is_free)
                        <span class="badge-muted">{{ __('Debaz') }}</span>
                    @else
                        <form method="POST" action="{{ route('admin.plans.toggle', $plan) }}">@csrf
                            <button class="badge {{ $plan->is_active ? 'badge-paid' : 'badge-muted' }}" title="{{ __('Chanje estati') }}">{{ $plan->is_active ? __('Aktif') : __('Inaktif') }}</button>
                        </form>
                    @endif
                </div>

                <p class="font-currency-display text-currency-display tabular-nums">
                    @if ($plan->is_free) 0 @elseif ($plan->price_monthly !== null) {{ number_format((float) $plan->price_monthly, 0, '.', ',') }} @else <span class="text-slate-400">—</span> @endif
                    <span class="font-title-md text-title-md text-on-surface-variant">{{ $cur }} / {{ __('mwa') }}</span>
                </p>
                @if (! $plan->is_free && $plan->price_monthly === null)
                    <p class="font-body-sm text-body-sm text-amber-700">{{ __('Fikse pri a anvan ou aktive plan sa a.') }}</p>
                @endif

                <ul class="font-body-md text-body-md space-y-1 text-on-surface-variant">
                    <li>{{ $plan->max_active_tontines ?? __('Ilimite') }} {{ __('tontin aktif') }}</li>
                    <li>{{ __('Jiska') }} {{ $plan->max_members_per_tontine ?? '∞' }} {{ __('manm pa tontin') }}</li>
                    <li>{{ count($plan->features ?? []) }} / {{ count(\App\Support\Features::ALL) }} {{ __('fonksyon') }}</li>
                </ul>

                <div class="flex items-center justify-between pt-1 border-t border-slate-100 font-body-sm text-body-sm text-on-surface-variant">
                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">group</span> {{ $plan->subscribers_count }} {{ __('abòne') }}</span>
                    <span>#{{ $plan->sort_order }}</span>
                </div>
                <a href="{{ route('admin.plans.edit', $plan) }}" class="btn-secondary btn-sm">{{ __('Modifye') }}</a>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.plans.rules') }}" class="card !p-space-lg space-y-space-md max-w-3xl">
        @csrf
        <h2 class="font-title-lg text-title-lg">{{ __('Règ jeneral yo') }}</h2>

        <div class="grid sm:grid-cols-2 gap-space-md">
            <div>
                <label class="label" for="grace_days">{{ __('Jou gras apre ekspirasyon') }}</label>
                <input id="grace_days" type="number" name="grace_days" min="0" max="60" class="input" value="{{ old('grace_days', $rules['grace_days']) }}" required>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ __('Pandan jou sa yo, kliyan an kenbe tout avantaj plan an. Apre sa, li tounen sou plan gratis la.') }}</p>
                <x-input-error :messages="$errors->get('grace_days')" class="mt-1" />
            </div>
            <div>
                <label class="label" for="remind_days">{{ __('Rapèl anvan ekspirasyon (jou)') }}</label>
                <input id="remind_days" type="number" name="remind_days" min="0" max="30" class="input" value="{{ old('remind_days', $rules['remind_days']) }}" required>
            </div>
        </div>

        <h3 class="font-title-md text-title-md pt-space-sm">{{ __('Kote kliyan yo voye peman an') }}</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant -mt-2">{{ __('Yon metòd parèt sou paj demann lan sèlman si ou ranpli li. Si ou pa ranpli okenn, pa gen moun ki ka mande abònman.') }}</p>
        <div class="grid sm:grid-cols-2 gap-space-md">
            <div><label class="label">{{ __('Nimewo MonCash') }}</label><input name="moncash_number" class="input" value="{{ old('moncash_number', $payment['moncash_number']) }}" placeholder="+509 ..."></div>
            <div><label class="label">{{ __('Non sou kont MonCash') }}</label><input name="moncash_name" class="input" value="{{ old('moncash_name', $payment['moncash_name']) }}"></div>
            <div><label class="label">{{ __('Nimewo NatCash') }}</label><input name="natcash_number" class="input" value="{{ old('natcash_number', $payment['natcash_number']) }}" placeholder="+509 ..."></div>
            <div><label class="label">{{ __('Non sou kont NatCash') }}</label><input name="natcash_name" class="input" value="{{ old('natcash_name', $payment['natcash_name']) }}"></div>
        </div>
        <div><label class="label">{{ __('Enfòmasyon bank (opsyonèl)') }}</label><textarea name="bank_info" rows="2" maxlength="300" class="input !min-h-0">{{ old('bank_info', $payment['bank_info']) }}</textarea></div>
        <div><label class="label">{{ __('Enstriksyon adisyonèl (opsyonèl)') }}</label><textarea name="instructions" rows="2" maxlength="400" class="input !min-h-0">{{ old('instructions', $payment['instructions']) }}</textarea></div>
        <button class="btn-primary"><span class="material-symbols-outlined">save</span> {{ __('Sove règ yo') }}</button>
    </form>
</x-admin-layout>
