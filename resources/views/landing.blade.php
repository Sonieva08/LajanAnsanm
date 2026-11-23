<x-public-page :title="__('Lajan ansanm, pi fò ansanm')">

    {{-- Hero : chaleureux, avec des formes douces --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-primary-fixed/70 via-surface to-surface">
        <div class="pointer-events-none absolute -top-24 -left-24 w-96 h-96 rounded-full bg-tertiary-fixed/50 blur-3xl"></div>
        <div class="pointer-events-none absolute top-40 -right-32 w-[28rem] h-[28rem] rounded-full bg-secondary-fixed/60 blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-4 md:px-8 py-14 lg:py-24 grid lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-6 flex flex-col items-center lg:items-start text-center lg:text-left gap-5">
                <span class="inline-flex items-center gap-2 bg-surface-container-lowest/90 px-4 py-1.5 rounded-full shadow-sm font-label-md text-label-md text-primary">
                    <span class="material-symbols-outlined text-[18px] text-on-tertiary-container" style="font-variation-settings:'FILL' 1">favorite</span>
                    {{ __('Bonjou, byenvini nan LajanAnsanm') }}
                </span>
                <h1 class="font-headline-xl-mobile lg:font-headline-xl text-primary font-extrabold tracking-tight max-w-[600px]">{{ __('Lajan ansanm, pi fò ansanm') }}</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant max-w-[500px] leading-relaxed">{{ __('Jere sòl ou yo, kotizasyon yo ak vèsman yo nan yon sèl kote.') }}</p>
                <p class="font-body-lg text-body-lg text-on-surface-variant max-w-[500px] leading-relaxed">{{ __('Pa bezwen kaye ni kalkil konplike: tout bagay klè, pou tout moun nan gwoup la.') }}</p>

                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto pt-2">
                    <a href="{{ route('register') }}" class="min-h-[52px] px-8 rounded-2xl bg-primary text-on-primary font-label-lg text-label-lg shadow-lg hover:-translate-y-0.5 hover:shadow-xl transition-all flex items-center justify-center gap-2">
                        {{ __('Kreye yon kont') }} <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                    <a href="#kijan" class="min-h-[52px] px-8 rounded-2xl bg-surface-container-lowest text-primary font-label-lg text-label-lg shadow-sm hover:shadow-md transition-all flex items-center justify-center">
                        {{ __('Gade kijan li mache') }}
                    </a>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant pt-1">
                    {{ __('Deja gen kont?') }} <a href="{{ route('login') }}" class="text-primary font-label-md hover:underline">{{ __('Konekte') }}</a>
                </p>

                {{-- Rassurance : uniquement des faits vrais pour l'application --}}
                <ul class="flex flex-wrap justify-center lg:justify-start gap-2 pt-1">
                    @foreach ([
                        ['redeem', __('Eseye 3 mwa gratis')],
                        ['translate', __('Kreyòl ak Fransè')],
                        ['link', __('Yon lyen pèsonèl pou chak manm')],
                    ] as [$icon, $label])
                        <li class="inline-flex items-center gap-1.5 bg-surface-container-lowest/80 rounded-full px-3 py-1.5 shadow-sm font-label-md text-label-md text-on-surface-variant">
                            <span class="material-symbols-outlined text-[16px] text-on-tertiary-container" style="font-variation-settings:'FILL' 1">{{ $icon }}</span>{{ $label }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Illustration : trois personnes et des pièces qui circulent --}}
            <div class="lg:col-span-6 w-full max-w-[520px] mx-auto">
                <div class="relative bg-surface-container-lowest rounded-[2rem] shadow-xl p-6 lg:p-8">
                    <svg viewBox="0 0 420 320" class="w-full h-auto" fill="none" aria-hidden="true">
                        <circle cx="210" cy="160" r="130" fill="#EAEDFF"/>
                        <circle cx="210" cy="160" r="100" stroke="#BAC3FF" stroke-width="3" stroke-dasharray="4 8" stroke-linecap="round"/>
                        <path d="M210 60 Q 300 110 330 200" stroke="#00AC77" stroke-width="3" stroke-dasharray="2 8" stroke-linecap="round"/>
                        <path d="M330 220 Q 250 290 120 220" stroke="#00AC77" stroke-width="3" stroke-dasharray="2 8" stroke-linecap="round"/>
                        <path d="M110 190 Q 110 110 200 58" stroke="#00AC77" stroke-width="3" stroke-dasharray="2 8" stroke-linecap="round"/>
                        <circle cx="210" cy="62" r="26" fill="#0C2382"/>
                        <path d="M176 110C176 94 191 84 210 84C229 84 244 94 244 110V118H176Z" fill="#001058"/>
                        <circle cx="112" cy="222" r="26" fill="#00AC77"/>
                        <path d="M78 270C78 252 93 242 112 242C131 242 146 252 146 270V278H78Z" fill="#00875A"/>
                        <circle cx="308" cy="222" r="26" fill="#FF6B7A"/>
                        <path d="M274 270C274 252 289 242 308 242C327 242 342 252 342 270V278H274Z" fill="#E2213D"/>
                        <g><circle cx="300" cy="120" r="17" fill="#F59E0B"/><circle cx="300" cy="120" r="12" fill="#FDE68A"/><text x="300" y="125" font-size="13" font-weight="800" text-anchor="middle" fill="#78350F" font-family="Inter,sans-serif">G</text></g>
                        <g><circle cx="120" cy="120" r="17" fill="#F59E0B"/><circle cx="120" cy="120" r="12" fill="#FDE68A"/><text x="120" y="125" font-size="13" font-weight="800" text-anchor="middle" fill="#78350F" font-family="Inter,sans-serif">G</text></g>
                        <g><circle cx="210" cy="200" r="19" fill="#D97706"/><circle cx="210" cy="200" r="14" fill="#FBBF24"/><text x="210" y="206" font-size="15" font-weight="800" text-anchor="middle" fill="#78350F" font-family="Inter,sans-serif">G</text></g>
                    </svg>
                    <div class="absolute -bottom-4 -left-3 sm:left-4 bg-surface-container-lowest rounded-2xl shadow-lg px-4 py-3 flex items-center gap-3">
                        <span class="w-9 h-9 rounded-full bg-tertiary-fixed flex items-center justify-center"><span class="material-symbols-outlined text-[20px] text-tertiary-container">check</span></span>
                        <span class="font-label-md text-label-md text-on-surface">{{ __('Kotizasyon peye!') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Une tradition qu'on connaît, en plus simple --}}
    <section class="max-w-4xl mx-auto px-4 md:px-8 pt-4 pb-6 text-center">
        <span class="material-symbols-outlined text-[32px] text-on-tertiary-container" style="font-variation-settings:'FILL' 1">diversity_1</span>
        <h2 class="font-headline-lg text-headline-lg text-primary mt-2">{{ __('Yon tradisyon nou konnen, pi senp') }}</h2>
        <p class="font-body-lg text-body-lg text-on-surface-variant mt-3 leading-relaxed max-w-2xl mx-auto">{{ __('Sòl la se yon bèl tradisyon ki baze sou konfyans. LajanAnsanm pa chanje fason l mache: li jis ede w kenbe kont yo klè pou pa gen konfizyon.') }}</p>
    </section>

    {{-- Pou ki moun --}}
    <section id="pou-ki-moun" class="max-w-7xl mx-auto px-4 md:px-8 py-16 lg:py-20">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="font-headline-lg text-headline-lg text-primary">{{ __('Pou ki moun ou ye?') }}</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-3">{{ __('Chak moun nan sòl la gen yon plas, ak sa li bezwen.') }}</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach ([
                ['manage_accounts', 'bg-primary-fixed text-primary', __('Pou òganizatè'), __('Kreye sòl, jere manm yo, epi swiv kotizasyon yo.')],
                ['diversity_3', 'bg-tertiary-fixed text-tertiary-container', __('Pou manm yo'), __('Wè kalandriye a epi deklare peman ou, ak yon simple lyen.')],
                ['volunteer_activism', 'bg-secondary-fixed text-secondary', __('Pou benefisyè a'), __('Konnen ki lè w ap resevwa lajan ou, epi konfime lè li rive.')],
            ] as [$icon, $tone, $title, $text])
                <div class="group bg-surface-container-lowest rounded-3xl p-7 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    <div class="w-14 h-14 rounded-2xl {{ $tone }} flex items-center justify-center mb-5">
                        <span class="material-symbols-outlined text-[28px]" style="font-variation-settings:'FILL' 1">{{ $icon }}</span>
                    </div>
                    <h3 class="font-title-lg text-title-lg text-on-surface font-bold mb-2">{{ $title }}</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Kijan li mache --}}
    <section id="kijan" class="bg-surface-container-low py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 md:px-8">
            <div class="mb-12 text-center">
                <h2 class="font-headline-lg text-headline-lg text-primary">{{ __('Kijan li mache') }}</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-3">{{ __('Twa etap klè pou mennen sòl ou san tèt chaje.') }}</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6 relative">
                @foreach ([
                    ['group_add', __('Kreye sòl la'), __('Kreye sòl ou an epi envite manm ou yo fasilman.'), __('Etap 01')],
                    ['payments', __('Ranmase kotizasyon yo'), __('Swiv tout moun ki peye alè chak semèn oswa chak mwa.'), __('Etap 02')],
                    ['account_balance', __('Vèse benefisyè a'), __('Konfime peman pou manm ki gen tou a san konplikasyon.'), __('Etap 03')],
                ] as [$icon, $title, $text, $step])
                    <div class="bg-surface-container-lowest rounded-3xl p-7 shadow-sm flex flex-col gap-5">
                        <div class="flex items-center justify-between">
                            <div class="w-14 h-14 rounded-2xl bg-primary text-on-primary flex items-center justify-center shadow-md">
                                <span class="material-symbols-outlined text-[26px]">{{ $icon }}</span>
                            </div>
                            <span class="font-label-sm text-label-sm text-primary/60">{{ $step }}</span>
                        </div>
                        <div>
                            <h3 class="font-title-lg text-title-lg text-on-surface font-bold mb-1">{{ $title }}</h3>
                            <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Moyens de paiement réellement pris en charge --}}
    <section class="max-w-5xl mx-auto px-4 md:px-8 py-14 text-center">
        <h2 class="font-headline-md text-headline-md text-primary">{{ __('Anrejistre peman yo jan ou konn fè l') }}</h2>
        <p class="font-body-md text-body-md text-on-surface-variant mt-2">{{ __('MonCash, NatCash, lajan kach oswa vireman labank.') }}</p>
        <div class="flex flex-wrap items-center justify-center gap-4 mt-6">
            <div class="flex items-center gap-3 bg-surface-container-lowest rounded-2xl shadow-sm px-5 py-3">
                <x-payment-method-logo method="moncash" size="h-10 w-10" /><span class="font-label-lg text-label-lg">MonCash</span>
            </div>
            <div class="flex items-center gap-3 bg-surface-container-lowest rounded-2xl shadow-sm px-5 py-3">
                <x-payment-method-logo method="natcash" size="h-10 w-10" /><span class="font-label-lg text-label-lg">NatCash</span>
            </div>
            <div class="flex items-center gap-3 bg-surface-container-lowest rounded-2xl shadow-sm px-5 py-3">
                <span class="w-10 h-10 rounded-lg bg-tertiary-fixed text-tertiary-container flex items-center justify-center"><span class="material-symbols-outlined text-[22px]">payments</span></span>
                <span class="font-label-lg text-label-lg">{{ __('Lajan kach') }}</span>
            </div>
            <div class="flex items-center gap-3 bg-surface-container-lowest rounded-2xl shadow-sm px-5 py-3">
                <span class="w-10 h-10 rounded-lg bg-primary-fixed text-primary flex items-center justify-center"><span class="material-symbols-outlined text-[22px]">account_balance</span></span>
                <span class="font-label-lg text-label-lg">{{ __('Vireman labank') }}</span>
            </div>
        </div>
    </section>

    {{-- Transparence --}}
    <section class="max-w-7xl mx-auto px-4 md:px-8 py-10">
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="rounded-3xl bg-gradient-to-br from-primary to-primary-container text-on-primary p-8 lg:p-10 shadow-lg flex flex-col gap-4">
                <span class="material-symbols-outlined text-[36px]" style="font-variation-settings:'FILL' 1">shield</span>
                <h2 class="font-headline-md text-headline-md font-bold">{{ __('LajanAnsanm pa kenbe lajan w') }}</h2>
                <p class="font-body-md text-body-md text-primary-fixed leading-relaxed">{{ __('Lajan an ale dirèkteman ant manm yo. Nou sèlman ede w swiv li.') }}</p>
            </div>
            <div class="rounded-3xl bg-tertiary-fixed/60 text-on-tertiary-fixed p-8 lg:p-10 shadow-sm flex flex-col gap-4">
                <span class="material-symbols-outlined text-[36px] text-tertiary-container" style="font-variation-settings:'FILL' 1">visibility</span>
                <h2 class="font-headline-md text-headline-md font-bold text-tertiary-container">{{ __('Chak moun wè sa ki bon pou li') }}</h2>
                <p class="font-body-md text-body-md text-on-surface leading-relaxed">{{ __('Chak manm wè sèlman pwòp peman li ak kalandriye a. Tout moun konnen ki lè chak benefisyè ap resevwa.') }}</p>
            </div>
        </div>
    </section>

    {{-- Avi moun yo : seulement de vrais avis publiés après modération --}}
    <section id="avi" class="bg-surface-container-low py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 md:px-8">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="font-headline-lg text-headline-lg text-primary">{{ __('Sa moun yo di') }}</h2>
                @if ($summary['count'] > 0)
                    <div class="mt-3 inline-flex flex-wrap items-center justify-center gap-2">
                        <x-star-rating :value="$summary['avg']" size="text-[24px]" />
                        <span class="font-title-md text-title-md text-on-surface">{{ __(':avg sou 5', ['avg' => number_format($summary['avg'], 1)]) }}</span>
                        <span class="font-body-md text-body-md text-on-surface-variant">· {{ __(':count avi', ['count' => $summary['count']]) }}</span>
                    </div>
                @else
                    <p class="font-body-md text-body-md text-on-surface-variant mt-3">{{ __('Poko gen avi ankò. Se ou ki ka premye a!') }}</p>
                @endif
            </div>

            @if ($reviews->isNotEmpty())
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach ($reviews as $review)
                        <figure class="bg-surface-container-lowest rounded-3xl p-7 shadow-sm flex flex-col gap-4">
                            <x-star-rating :value="$review->rating" />
                            <blockquote class="font-body-lg text-body-lg text-on-surface leading-relaxed break-words">{{ $review->comment }}</blockquote>
                            <figcaption class="mt-auto flex items-center gap-3">
                                <span class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-label-sm text-label-sm">{{ mb_strtoupper(mb_substr($review->displayName(), 0, 1)) }}</span>
                                <span class="font-label-lg text-label-lg text-on-surface-variant">{{ $review->displayName() }}</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-3 justify-center mt-10">
                <a href="{{ route('reviews.index') }}" class="min-h-[48px] px-7 rounded-2xl bg-primary text-on-primary font-label-lg text-label-lg shadow-md hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings:'FILL' 1">rate_review</span>{{ __('Bay avi ou') }}
                </a>
                @if ($summary['count'] > $reviews->count())
                    <a href="{{ route('reviews.index') }}" class="min-h-[48px] px-7 rounded-2xl bg-surface-container-lowest text-primary font-label-lg text-label-lg shadow-sm hover:shadow-md transition-all flex items-center justify-center">{{ __('Wè tout avi yo') }}</a>
                @endif
            </div>
        </div>
    </section>

    {{-- Kesyon --}}
    <section id="kesyon" class="max-w-3xl mx-auto px-4 md:px-8 py-16">
        <h2 class="font-headline-lg text-headline-lg text-primary text-center mb-10">{{ __('Kesyon yo poze souvan') }}</h2>
        @php $wa = preg_replace('/\D/', '', (string) config('lajanansanm.support_whatsapp')); @endphp
        <div class="space-y-3">
            @foreach ([
                [__('Kisa k fè si mwen pa gen kont?'), __('Ou pa bezwen kont si òganizatè a voye yon lyen dirèk pou ou.')],
                [__('Èske mwen bezwen yon lyen?'), __('Wi, lyen pèsonèl la pèmèt ou swiv tou pa ou san w pa kreye kont.')],
                [__('Ki lang aplikasyon an pale?'), __('Kreyòl ak Fransè. Ou ka chanje lang lan nenpòt ki lè, anwo paj la.')],
                [__('Kijan mwen kontakte sipò a?'), $wa !== '' ? __('Ou ka ekri ekip sipò nou an sou WhatsApp.') : __('Pale ak òganizatè sòl ou a.')],
            ] as [$q, $a])
                <div x-data="{ open: false }" class="bg-surface-container-lowest rounded-2xl shadow-sm overflow-hidden">
                    <button type="button" @click="open = !open" :aria-expanded="open" class="w-full min-h-[60px] px-6 py-4 flex items-center justify-between gap-4 text-left">
                        <span class="font-title-md text-title-md text-on-surface font-semibold">{{ $q }}</span>
                        <span class="material-symbols-outlined text-primary transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                    </button>
                    <div x-show="open" x-cloak class="px-6 pb-5 font-body-md text-body-md text-on-surface-variant leading-relaxed">{{ $a }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Appel à l'action --}}
    <section class="max-w-7xl mx-auto px-4 md:px-8 pb-20">
        <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-r from-primary-container via-primary to-primary-container p-10 lg:p-14 text-center shadow-xl">
            <div class="pointer-events-none absolute -top-16 -right-10 w-64 h-64 rounded-full bg-tertiary-fixed/20 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-secondary-fixed/20 blur-2xl"></div>
            <div class="relative flex flex-col items-center gap-5">
                <h2 class="font-headline-lg text-headline-lg text-on-primary font-bold">{{ __('Eseye 3 mwa gratis') }}</h2>
                <p class="font-body-lg text-body-lg text-primary-fixed max-w-xl">{{ __('Tout fonksyon yo, san kat.') }}</p>
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="{{ route('register') }}" class="min-h-[52px] px-8 rounded-2xl bg-surface-container-lowest text-primary font-label-lg text-label-lg shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center">{{ __('Kreye yon kont') }}</a>
                    <a href="{{ route('login') }}" class="min-h-[52px] px-8 rounded-2xl border border-white/30 text-on-primary font-label-lg text-label-lg hover:bg-white/10 transition-colors flex items-center justify-center">{{ __('Konekte') }}</a>
                </div>
            </div>
        </div>
    </section>
</x-public-page>
