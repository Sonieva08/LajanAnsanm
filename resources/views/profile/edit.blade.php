<x-app-layout>
    @php
        $u = $user ?? auth()->user();
        $ent = \App\Support\Entitlements::for($u);
        $hasPassword = filled($u->password);
        $linked = (bool) $u->google_id;
        $running = \App\Http\Controllers\AccountController::runningTontines($u);
        $maxT = $ent->maxTontines();
        $maxM = $ent->maxMembers();
        $initials = collect(preg_split('/\s+/', trim($u->name)))->filter()->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
        $locale = $u->locale ?? app()->getLocale();
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-on-surface">{{ __('Profil ak paramèt kont') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 flex flex-col gap-space-lg">

            @if (session('status') === 'profile-updated')
                <div class="alert-ok">{{ __('Profil mete ajou.') }}</div>
            @elseif (session('status') === 'email-change-pending')
                <div class="alert-ok">{{ __('A confirmation link was sent to the new email address. Your current email remains active until you confirm it.') }}</div>
            @elseif (session('status') === 'email-change-completed')
                <div class="alert-ok">{{ __('Your email address has been changed and verified.') }}</div>
            @elseif (session('status') === 'email-change-link-sent')
                <div class="alert-ok">{{ __('A new confirmation link has been sent.') }}</div>
            @elseif (session('status'))
                <div class="alert-ok">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert-err">{{ $errors->first() }}</div>
            @endif

            {{-- Carte d'identité --}}
            <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-space-md">
                <div class="flex items-center gap-space-md min-w-0">
                    <div class="w-16 h-16 rounded-full bg-primary-fixed text-primary flex items-center justify-center font-headline-md text-headline-md font-bold shrink-0 shadow-inner">
                        {{ $initials }}
                    </div>
                    <div class="flex flex-col min-w-0">
                        <div class="flex items-center gap-space-sm flex-wrap">
                            <span class="font-title-lg text-title-lg text-on-surface font-semibold truncate">{{ $u->name }}</span>
                            <span class="inline-flex px-2.5 py-0.5 rounded-full font-label-sm text-label-sm bg-surface-container text-on-surface-variant">{{ $ent->plan->name }}</span>
                        </div>
                        <span class="font-body-md text-body-md text-on-surface-variant truncate">{{ $u->email }}</span>
                    </div>
                </div>
                @if ($u->email_verified_at)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-label-sm text-label-sm bg-surface-container-high text-primary font-semibold self-start sm:self-center">
                        <span class="material-symbols-outlined text-[16px]">verified_user</span>{{ __('Manm verifye') }}
                    </span>
                @endif
            </div>

            {{-- Enfòmasyon pèsonèl --}}
            <section class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col gap-space-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-space-sm">
                        <span class="material-symbols-outlined text-primary text-[22px]">badge</span>
                        <h3 class="font-title-lg text-title-lg text-on-surface font-semibold">{{ __('Enfòmasyon pèsonèl') }}</h3>
                    </div>
                    <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">{{ __('Detay pwofil') }}</span>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                    @csrf @method('patch')
                    <div class="flex flex-col gap-space-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant" for="name">{{ __('Non konplè') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $u->name) }}" required class="input">
                    </div>
                    <div class="flex flex-col gap-space-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant" for="email">{{ __('Imèl') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $u->email) }}" required class="input">
                    </div>
                    @if ($hasPassword)
                        <div class="flex flex-col gap-space-xs md:col-span-2">
                            <label class="font-label-md text-label-md text-on-surface-variant" for="current_password">{{ __('Modpas aktyèl (obligatwa pou chanje imèl)') }}</label>
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="input">
                        </div>
                    @endif
                    <div class="md:col-span-2 flex justify-end">
                        <button class="btn-primary">{{ __('Sove') }}</button>
                    </div>
                </form>

                @if ($u->pending_email)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm rounded-xl bg-surface-container-low p-space-md">
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ __('Email change pending confirmation at :email.', ['email' => $u->pending_email]) }}
                        </p>
                        <form method="POST" action="{{ route('profile.email.resend') }}">
                            @csrf
                            <button class="btn-secondary btn-sm shrink-0">{{ __('Resend confirmation link') }}</button>
                        </form>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.preferences') }}" class="flex flex-col gap-space-md pt-space-sm border-t border-slate-100">
                    @csrf
                    <div class="flex flex-col gap-space-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant" for="phone">{{ __('Telefòn') }}</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $u->phone) }}" placeholder="+509 ..." class="input">
                    </div>

                    <div class="flex flex-col gap-space-sm">
                        <span class="font-label-md text-label-md text-on-surface-variant">{{ __('Chwa lang pou koòdone a') }}</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-md">
                            <label class="cursor-pointer">
                                <input type="radio" name="locale" value="ht" class="peer sr-only" @checked($locale === 'ht')>
                                <span class="h-16 px-4 rounded-xl flex items-center justify-between transition-all bg-surface-container-low text-on-surface-variant peer-checked:bg-surface-container-high peer-checked:text-primary peer-checked:shadow-sm">
                                    <span class="flex items-center gap-space-sm">
                                        <span class="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center font-label-md text-label-md">HT</span>
                                        <span class="flex flex-col text-left">
                                            <span class="font-title-md text-title-md font-semibold">{{ __('Kreyòl Ayisyen') }}</span>
                                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Lang natif platfòm nan') }}</span>
                                        </span>
                                    </span>
                                    <span class="material-symbols-outlined text-primary text-[20px]" style="font-variation-settings:'FILL' 1">check_circle</span>
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="locale" value="fr" class="peer sr-only" @checked($locale === 'fr')>
                                <span class="h-16 px-4 rounded-xl flex items-center justify-between transition-all bg-surface-container-low text-on-surface-variant peer-checked:bg-surface-container-high peer-checked:text-primary peer-checked:shadow-sm">
                                    <span class="flex items-center gap-space-sm">
                                        <span class="w-8 h-8 rounded-full bg-surface-variant text-on-surface-variant flex items-center justify-center font-label-md text-label-md">FR</span>
                                        <span class="flex flex-col text-left">
                                            <span class="font-title-md text-title-md font-semibold">Français</span>
                                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Lang segondè') }}</span>
                                        </span>
                                    </span>
                                    <span class="material-symbols-outlined text-primary text-[20px]" style="font-variation-settings:'FILL' 1">check_circle</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button class="btn-primary">{{ __('Sove modifikasyon yo') }}</button>
                    </div>
                </form>
            </section>

            {{-- Sekirite --}}
            <section class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-space-sm">
                        <span class="material-symbols-outlined text-primary text-[22px]">security</span>
                        <h3 class="font-title-lg text-title-lg text-on-surface font-semibold">{{ __('Sekirite ak koneksyon') }}</h3>
                    </div>
                    <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">{{ __('Pwoteksyon') }}</span>
                </div>

                {{-- Google --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-space-md rounded-xl bg-surface-container-low gap-space-sm">
                    <div class="flex items-center gap-space-md min-w-0">
                        <div class="w-10 h-10 rounded-lg bg-surface-container-lowest flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px] text-primary">account_circle</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-title-md text-title-md text-on-surface font-semibold truncate">{{ __('Konekte ak Google') }}</span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant truncate">{{ $linked ? __('Sèvi ak kont Google :email', ['email' => $u->email]) : __('Non lye') }}</span>
                        </div>
                    </div>
                    <span class="{{ $linked ? 'badge-paid' : 'badge-muted' }} self-end sm:self-center">{{ $linked ? __('Lye') : __('Non lye') }}</span>
                </div>

                {{-- Modpas --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-space-md rounded-xl bg-surface-container-low gap-space-sm">
                    <div class="flex items-center gap-space-md min-w-0">
                        <div class="w-10 h-10 rounded-lg bg-surface-container-lowest text-primary flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px]">password</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-title-md text-title-md text-on-surface font-semibold truncate">{{ __('Modpas sekirite') }}</span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Definisyon yon modpas si ou pa vle pase pa Google sèlman.') }}</span>
                        </div>
                    </div>
                    @if ($hasPassword)
                        <a href="#chanje-modpas" class="btn-secondary btn-sm shrink-0 self-end sm:self-center">{{ __('Chanje modpas la') }}</a>
                    @else
                        <a href="{{ route('password.set') }}" class="btn-secondary btn-sm shrink-0 self-end sm:self-center">{{ __('Mete yon modpas') }}</a>
                    @endif
                </div>

                @if ($hasPassword)
                    <form id="chanje-modpas" method="POST" action="{{ route('password.update') }}" class="grid grid-cols-1 md:grid-cols-3 gap-space-sm">
                        @csrf @method('put')
                        <input name="current_password" type="password" class="input" placeholder="{{ __('Modpas aktyèl') }}" autocomplete="current-password" required>
                        <input name="password" type="password" class="input" placeholder="{{ __('Nouvo modpas') }}" autocomplete="new-password" required>
                        <input name="password_confirmation" type="password" class="input" placeholder="{{ __('Konfime modpas') }}" autocomplete="new-password" required>
                        <div class="md:col-span-3 flex justify-end">
                            <button class="btn-primary">{{ __('Sove modpas la') }}</button>
                        </div>
                    </form>
                @endif

                {{-- 2FA (òganizatè sèlman) --}}
                @if ($u->is_owner)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-space-md rounded-xl bg-surface-container-low gap-space-sm">
                        <div class="flex items-center gap-space-md min-w-0">
                            <div class="w-10 h-10 rounded-lg bg-surface-container-lowest text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[20px]">phonelink_lock</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <div class="flex items-center gap-space-xs flex-wrap">
                                    <span class="font-title-md text-title-md text-on-surface font-semibold">{{ __('Verifikasyon 2 etap (2FA)') }}</span>
                                    <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm bg-surface-variant text-on-surface-variant">{{ __('Òganizatè sèlman') }}</span>
                                </div>
                                <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Kòd 6 chif pa aplikasyon otantifikasyon.') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-space-sm shrink-0 self-end sm:self-center">
                            <span class="{{ $u->two_factor_confirmed_at ? 'badge-paid' : 'badge-muted' }}">{{ $u->two_factor_confirmed_at ? __('Aktif') : __('Inaktif') }}</span>
                            <a href="{{ route('admin.settings.security') }}" class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center text-on-surface-variant hover:text-primary">
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </a>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Abònman --}}
            <section class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col gap-space-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-space-sm">
                        <span class="material-symbols-outlined text-primary text-[22px]">workspace_premium</span>
                        <h3 class="font-title-lg text-title-lg text-on-surface font-semibold">{{ __('Abònman mwen') }}</h3>
                    </div>
                    <span class="{{ $ent->isPaid() || $ent->state === 'owner' ? 'badge-paid' : 'badge-muted' }}">{{ $ent->isPaid() ? __('Aktif') : __('Plan gratis') }}</span>
                </div>
                <div class="p-space-md rounded-xl bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-space-md">
                    <div class="flex flex-col gap-space-xs">
                        <span class="font-title-lg text-title-lg text-primary font-bold">{{ $ent->plan->name }}</span>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            {{ __('Limit aktyèl: :t sòl aktif ak yon maksimòm :m manm.', ['t' => $maxT ?? __('Ilimite'), 'm' => $maxM ?? __('Ilimite')]) }}
                        </p>
                    </div>
                    <a href="{{ route('subscription') }}" class="btn-secondary shrink-0">{{ __('Wè detay oswa amelyore plan') }}</a>
                </div>
            </section>

            {{-- Done mwen --}}
            <section class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-space-md">
                <div class="flex flex-col gap-space-xs max-w-xl">
                    <div class="flex items-center gap-space-sm">
                        <span class="material-symbols-outlined text-primary text-[22px]">cloud_download</span>
                        <h3 class="font-title-lg text-title-lg text-on-surface font-semibold">{{ __('Done mwen') }}</h3>
                    </div>
                    <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Telechaje yon kopi done ou yo an JSON oswa CSV.') }}</p>
                </div>
                <div class="flex gap-space-sm shrink-0">
                    <a href="{{ route('account.export') }}" class="btn-secondary">{{ __('Telechaje JSON') }}</a>
                    <a href="{{ route('account.export', ['format' => 'csv']) }}" class="btn-secondary">{{ __('Telechaje CSV') }}</a>
                </div>
            </section>

            {{-- Zòn danje --}}
            <section class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col gap-space-md border-t-2 border-error">
                <div class="flex items-center gap-space-sm text-error">
                    <span class="material-symbols-outlined text-[22px]">warning</span>
                    <h3 class="font-title-lg text-title-lg font-semibold">{{ __('Zòn danje') }}</h3>
                </div>
                <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Efase kont ou se yon aksyon ki pa ka defèt.') }}</p>

                @if ($running > 0)
                    <div class="p-space-md rounded-xl bg-surface-container-low flex items-start gap-space-sm">
                        <span class="material-symbols-outlined text-error text-[20px] shrink-0">lock</span>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Ou gen sòl an kou. Fini yo oswa mete yo an pòz anvan ou efase kont ou.') }}</p>
                    </div>
                    <button type="button" disabled class="h-12 px-6 rounded-lg bg-surface-container-high text-outline cursor-not-allowed opacity-60 self-end">{{ __('Efase kont mwen') }}</button>
                @else
                    <form method="POST" action="{{ route('account.destroy') }}" class="flex flex-col gap-space-sm"
                          onsubmit="return confirm('{{ __('Efase kont ou pou tout tan?') }}')">
                        @csrf @method('delete')
                        @if ($hasPassword)
                            <input name="password" type="password" class="input" placeholder="{{ __('Modpas aktyèl') }}" autocomplete="current-password" required>
                        @else
                            <input name="confirm" class="input" placeholder="EFASE" required>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Ekri EFASE pou konfime') }}</p>
                        @endif
                        <button class="btn-danger self-end">{{ __('Efase kont mwen') }}</button>
                    </form>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
