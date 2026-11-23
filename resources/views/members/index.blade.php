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
        @if (session('status') === 'member-invitation-created')
            <div class="alert-ok">
                {{ __('Envitation an kreye. Pataje lyen an pou moun nan aksepte.') }}
                @if (session('invitation-email-status') === 'sent')
                    {{ __('Imèl envitasyon an voye.') }}
                @elseif (session('invitation-email-status') === 'failed')
                    {{ __('Imèl la pa t voye; itilize WhatsApp, SMS oswa lyen an.') }}
                @elseif (session('invitation-email-status') === 'not-provided')
                    {{ __('Pa gen adrès imèl; pataje lyen an sou WhatsApp oswa SMS.') }}
                @endif
            </div>
        @elseif (session('status'))
            <div class="alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->has('locked'))<div class="alert-err">{{ $errors->first('locked') }}</div>@endif
        @if ($errors->has('invitation'))<div class="alert-err">{{ $errors->first('invitation') }}</div>@endif
        @if ($errors->has('cycles'))<div class="alert-err">{{ $errors->first('cycles') }}</div>@endif

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
            <div x-data="{ open: {{ $errors->has('name') || $errors->has('phone') || $errors->has('consent') ? 'true' : 'false' }} }">
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
                    <label class="flex items-start gap-2 font-body-sm text-body-sm text-on-surface">
                        <input type="checkbox" name="consent" value="1" required class="mt-1 rounded border-slate-300 text-primary-container">
                        <span>{{ __('Manm nan dakò pou nou kenbe non ak nimewo telefòn li pou jere sòl la.') }}
                            <a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="underline text-primary-container">{{ __('Politik konfidansyalite') }}</a></span>
                    </label>
                    <x-input-error :messages="$errors->get('consent')" class="mt-1" />
                    <button class="btn-primary w-full">{{ __('Save') }}</button>
                </form>
            </div>
            <div class="flex gap-2 bg-primary-fixed/60 text-on-primary-fixed-variant p-space-sm rounded-xl font-body-sm text-body-sm">
                <span class="material-symbols-outlined text-[18px]">lightbulb</span>
                {{ __('Ou ka monte oswa desann manm yo pou chanje lòd resepsyon an anvan sòl la kòmanse.') }}
            </div>
        @else
            <div class="flex gap-2 bg-primary-fixed/60 text-on-primary-fixed-variant p-space-sm rounded-xl font-body-sm text-body-sm">
                <span class="material-symbols-outlined text-[18px]">lock</span>
                {{ __('Sòl la deja kòmanse. Lis manm yo bloke.') }}
            </div>
        @endif

        @if (! $locked)
            @if ($tontine->member_portal_enabled)
                <a class="btn-secondary w-full" href="{{ route('invitations.create', $tontine) }}">
                    <span class="material-symbols-outlined">person_add</span>{{ __('Envite yon moun') }}
                </a>
                <section class="card space-y-space-md">
                    <div>
                        <h2 class="font-title-lg text-title-lg">{{ __('Envite yon moun nan sòl la') }}</h2>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ __('Moun nan ap bezwen aksepte envitasyon an anvan li antre. Apre sa lyen pèsonèl li a ap rete aktif pandan sòl la ouvè.') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('tontines.members.invite', $tontine) }}" class="space-y-space-sm">
                        @csrf
                        <div>
                            <label class="label" for="invite-name">{{ __('Non') }}</label>
                            <input id="invite-name" name="name" class="input" value="{{ old('name') }}" required>
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>
                        <div>
                            <label class="label" for="invite-phone">{{ __('Telefòn (pou WhatsApp oswa SMS)') }}</label>
                            <input id="invite-phone" name="phone" type="tel" class="input" value="{{ old('phone') }}" placeholder="+509 ...">
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                        <div>
                            <label class="label" for="invite-email">{{ __('Adrès imèl (pou voye envitasyon an otomatikman)') }}</label>
                            <input id="invite-email" name="email" type="email" class="input" value="{{ old('email') }}" placeholder="non@imel.com">
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                        <label class="flex items-start gap-2 font-body-sm text-body-sm text-on-surface">
                            <input type="checkbox" name="contact_permission" value="1" required class="mt-1 rounded border-slate-300 text-primary-container">
                            <span>{{ __('Mwen gen pèmisyon pou m itilize kontak sa a pou voye envitasyon an.') }}</span>
                        </label>
                        <x-input-error :messages="$errors->get('contact_permission')" class="mt-1" />
                        <button class="btn-primary w-full">
                            <span class="material-symbols-outlined">send</span> {{ __('Kreye epi voye envitasyon') }}
                        </button>
                    </form>
                </section>
            @else
                <div class="card flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm">
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Ouvri pòtay manm yo anvan ou voye envitasyon.') }}</p>
                    <a class="btn-secondary shrink-0" href="{{ route('tontines.portal.edit', $tontine) }}">{{ __('Konfigire pòtay manm yo') }}</a>
                </div>
            @endif
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
                <x-empty-state art="members" :title="__('Pa gen manm nan sòl sa a ankò')" :text="__('Ajoute premye patisipan yo ak nimewo telefòn yo pou prepare kalandriye tiraj la.')" />
            @endforelse
        </div>

        @if ($pendingInvitations->isNotEmpty())
            <section class="space-y-space-sm">
                <div>
                    <h2 class="font-title-md text-title-md">{{ __('Envitasyon k ap tann') }}</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Sòl la pa ka kòmanse toutotan gen envitasyon k ap tann.') }}</p>
                </div>
                @foreach ($pendingInvitations as $invite)
                    @php
                        $inviteUrl = $invite->invitationUrl();
                        $inviteText = __('Ou envite nan sòl :tontine. Louvri lyen sa a pou aksepte epi antre nan pòtay pèsonèl ou:', ['tontine' => $tontine->name]).' '.$inviteUrl;
                        $smsNumber = preg_replace('/[^\d+]/', '', (string) $invite->phone);
                        $waNumber = $invite->whatsappNumber();
                    @endphp
                    <div class="card flex flex-col sm:flex-row sm:items-center gap-space-sm">
                        <div class="flex-1 min-w-0">
                            <p class="font-title-md text-title-md truncate">{{ $invite->name }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant truncate">
                                {{ $invite->email ?: $invite->phone }} · {{ __('Ap tann akseptasyon') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                x-data="{ copied: false }"
                                data-invitation-url="{{ $inviteUrl }}"
                                @click="navigator.clipboard.writeText($el.dataset.invitationUrl); copied = true; setTimeout(() => copied = false, 1500)"
                                class="btn-secondary btn-sm" :aria-label="copied ? @js(__('Lyen kopye')) : @js(__('Kopye lyen envitasyon an'))">
                                <span class="material-symbols-outlined text-[18px]" x-text="copied ? 'check' : 'content_copy'">content_copy</span>
                                <span x-text="copied ? @js(__('Lyen kopye')) : @js(__('Kopye lyen'))">{{ __('Kopye lyen') }}</span>
                            </button>
                            @if ($waNumber)
                                <a class="btn-secondary btn-sm" href="https://wa.me/{{ $waNumber }}?text={{ rawurlencode($inviteText) }}" target="_blank" rel="noopener noreferrer">
                                    <span class="material-symbols-outlined text-[18px]">chat</span> WhatsApp
                                </a>
                            @endif
                            @if ($smsNumber !== '')
                                <a class="btn-secondary btn-sm" href="sms:{{ $smsNumber }}?body={{ rawurlencode($inviteText) }}">
                                    <span class="material-symbols-outlined text-[18px]">sms</span> SMS
                                </a>
                            @endif
                            @if ($invite->email)
                                <a class="btn-secondary btn-sm" href="mailto:{{ $invite->email }}?subject={{ rawurlencode(__('Envitasyon pou antre nan :tontine sou LajanAnsanm', ['tontine' => $tontine->name])) }}&amp;body={{ rawurlencode($inviteText) }}">
                                    <span class="material-symbols-outlined text-[18px]">mail</span> {{ __('Imèl') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('tontines.members.destroy', [$tontine, $invite]) }}" onsubmit="return confirm('{{ __('Anile envitasyon sa a?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn-secondary btn-sm text-error">{{ __('Anile') }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

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
