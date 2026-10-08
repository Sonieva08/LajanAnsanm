<x-app-layout>
    @php
        $cur = config('lajanansanm.currency');
        $locked = $tontine->isLocked();
        $cycle = $currentCycle ?? null;
        $paidIds = $paidIds ?? [];
    @endphp

    <x-page-header :back="route('tontines.show', $tontine)" :title="__('Manm yo')" :subtitle="$tontine->name . ' · ' . $members->count() . ' ' . __('manm')">
        @if (! $locked && $members->count() > 1)
            <form method="POST" action="{{ route('tontines.members.shuffle', $tontine) }}" onsubmit="return confirm('{{ __('Melanje lòd la o aza?') }}')">
                @csrf
                <button class="w-11 h-11 flex items-center justify-center rounded-full bg-surface-container-lowest shadow-sm" title="{{ __('Tiraj o sò') }}">
                    <span class="material-symbols-outlined">shuffle</span>
                </button>
            </form>
        @endif
    </x-page-header>

    <div class="space-y-space-md">
        @if (session('status'))<div class="alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->has('locked'))<div class="alert-err">{{ $errors->first('locked') }}</div>@endif

        <div class="flex justify-between items-center bg-primary-fixed/60 rounded-xl p-space-md tabular-nums">
            <div>
                <p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Kotizasyon') }}</p>
                <p class="font-title-md text-title-md text-primary-container">{{ number_format($tontine->amount_per_period, 0, '.', ',') }} {{ $cur }}</p>
            </div>
            <div class="text-right">
                <p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Lajan total pou chak tou') }}</p>
                <p class="font-title-md text-title-md">{{ number_format($tontine->potAmount(), 0, '.', ',') }} {{ $cur }}</p>
            </div>
        </div>

        @if (! $locked)
            <div x-data="{ open: {{ $errors->has('name') || $errors->has('phone') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" class="btn-primary w-full">
                    <span class="material-symbols-outlined">person_add</span> {{ __('Ajoute manm') }}
                </button>
                <form x-show="open" x-cloak x-transition method="POST" action="{{ route('tontines.members.store', $tontine) }}" class="card mt-space-sm space-y-space-md">
                    @csrf
                    <div>
                        <label class="label" for="name">{{ __('Non') }}</label>
                        <input id="name" name="name" class="input" value="{{ old('name') }}" required>
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <label class="label" for="phone">{{ __('Telefòn') }}</label>
                        <input id="phone" name="phone" class="input" value="{{ old('phone') }}" placeholder="+509 ...">
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>
                    <button class="btn-primary w-full">{{ __('Save') }}</button>
                </form>
            </div>
            <div class="flex gap-2 bg-primary-fixed/60 text-on-primary-fixed-variant p-space-sm rounded-xl font-body-sm text-body-sm">
                <span class="material-symbols-outlined text-[18px]">lightbulb</span>
                {{ __('Ou ka monte oswa desann manm yo pou chanje lòd resepsyon an anvan tontin nan kòmanse.') }}
            </div>
        @else
            <div class="flex gap-2 bg-primary-fixed/60 text-on-primary-fixed-variant p-space-sm rounded-xl font-body-sm text-body-sm">
                <span class="material-symbols-outlined text-[18px]">lock</span>
                {{ __('Tontine a deja kòmanse. Lis manm yo bloke.') }}
            </div>
        @endif

        <div class="space-y-space-sm">
            @forelse ($members as $member)
                @php
                    $isBeneficiary = $cycle && $cycle->beneficiary_member_id === $member->id;
                    $hasPaid = in_array($member->id, $paidIds, true);
                @endphp
                <div class="card !p-space-sm" x-data="{ edit: false }">
                    <div class="flex items-center gap-3">
                        <x-avatar :name="$member->name" :rank="$member->position" />
                        <div class="flex-1 min-w-0">
                            <p class="font-title-md text-title-md truncate">{{ $member->name }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $member->phone ?: '—' }}</p>
                        </div>
                        @if ($cycle)
                            @if ($isBeneficiary)<span class="badge-info">★ {{ __('Tou sa a') }}</span>
                            @elseif ($hasPaid)<span class="badge-paid">{{ __('Peye') }}</span>
                            @elseif ($cycle->isLate())<span class="badge-late">{{ __('An reta') }}</span>
                            @else<span class="badge-wait">{{ __('Ap tann') }}</span>@endif
                        @endif
                    </div>

                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="edit = !edit" class="font-label-md text-label-md text-primary-container">{{ __('Edit') }}</button>

                        @unless ($locked)
                            <div class="flex items-center gap-1">
                                @if (! $loop->first)
                                    <form method="POST" action="{{ route('tontines.members.move', [$tontine, $member, 'up']) }}">@csrf
                                        <button class="w-10 h-10 rounded-lg bg-surface-container" title="{{ __('Monte') }}"><span class="material-symbols-outlined text-[20px]">arrow_upward</span></button></form>
                                @endif
                                @if (! $loop->last)
                                    <form method="POST" action="{{ route('tontines.members.move', [$tontine, $member, 'down']) }}">@csrf
                                        <button class="w-10 h-10 rounded-lg bg-surface-container" title="{{ __('Desann') }}"><span class="material-symbols-outlined text-[20px]">arrow_downward</span></button></form>
                                @endif
                                <form method="POST" action="{{ route('tontines.members.destroy', [$tontine, $member]) }}" onsubmit="return confirm('{{ __('Efase manm sa a?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="w-10 h-10 rounded-lg bg-error-container/60 text-secondary" title="{{ __('Delete') }}"><span class="material-symbols-outlined text-[20px]">delete</span></button></form>
                            </div>
                        @endunless
                    </div>

                    <form x-show="edit" x-cloak method="POST" action="{{ route('tontines.members.update', [$tontine, $member]) }}" class="grid gap-2 mt-2">
                        @csrf @method('PUT')
                        <input name="name" class="input" value="{{ $member->name }}" required>
                        <input name="phone" class="input" value="{{ $member->phone }}" placeholder="+509 ...">
                        <button class="btn-primary btn-sm">{{ __('Save') }}</button>
                    </form>
                </div>
            @empty
                <x-empty-state art="members" :title="__('Pa gen manm nan tontin sa a ankò')" :text="__('Ajoute premye patisipan yo ak nimewo telefòn yo pou prepare kalandriye tiraj la.')" />
            @endforelse
        </div>

        @if (($inactive ?? collect())->isNotEmpty())
            <div class="pt-space-md space-y-space-sm">
                <h2 class="font-title-md text-title-md text-on-surface-variant">{{ __('Manm ki retire') }}</h2>
                @foreach ($inactive as $m)
                    <div class="card !p-space-sm flex items-center gap-3 opacity-70">
                        <x-avatar :name="$m->name" tone="bg-slate-100 text-slate-500" />
                        <div class="flex-1 min-w-0">
                            <p class="font-title-md text-title-md truncate">{{ $m->name }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $m->phone ?: '—' }}</p>
                        </div>
                        @unless ($locked)
                            <form method="POST" action="{{ route('tontines.members.reactivate', [$tontine, $m]) }}">
                                @csrf
                                <button class="btn-secondary btn-sm">{{ __('Reaktive') }}</button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
