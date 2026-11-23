<x-public-page :title="__('Avi moun yo')">
    <div class="max-w-3xl mx-auto px-4 md:px-0 py-10 space-y-space-lg">

        <div class="text-center">
            <h1 class="font-headline-lg text-headline-lg text-primary">{{ __('Avi moun yo') }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">{{ __('Sa moun ki itilize LajanAnsanm ap di.') }}</p>
            @if ($summary['count'] > 0)
                <div class="mt-4 inline-flex flex-wrap items-center justify-center gap-2 bg-surface-container-lowest rounded-full shadow-sm px-5 py-2">
                    <x-star-rating :value="$summary['avg']" size="text-[24px]" />
                    <span class="font-title-md text-title-md">{{ __(':avg sou 5', ['avg' => number_format($summary['avg'], 1)]) }}</span>
                    <span class="font-body-md text-body-md text-on-surface-variant">· {{ __(':count avi', ['count' => $summary['count']]) }}</span>
                </div>
            @endif
        </div>

        @if (session('status'))
            <div class="alert-ok" role="status">{{ session('status') }}</div>
        @endif

        {{-- Formulaire : réservé aux comptes connectés --}}
        @auth
            <section class="card !p-space-lg space-y-space-md"
                     x-data="{ rating: {{ (int) old('rating', $mine->rating ?? 0) }}, hover: 0, text: @js(old('comment', $mine->comment ?? '')) }">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-title-lg text-title-lg text-on-surface">{{ $mine ? __('Avi ou') : __('Bay avi ou') }}</h2>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">{{ __('Di nou kijan LajanAnsanm ede w. Avi ou ka ede lòt òganizatè.') }}</p>
                    </div>
                    @if ($mine)
                        <span class="{{ ['pending' => 'badge-wait', 'approved' => 'badge-paid', 'hidden' => 'badge-muted'][$mine->status] ?? 'badge-muted' }}">
                            {{ ['pending' => __('Ap tann apwobasyon'), 'approved' => __('Pibliye'), 'hidden' => __('Kache')][$mine->status] ?? $mine->status }}
                        </span>
                    @endif
                </div>

                <form method="POST" action="{{ route('reviews.store') }}" class="space-y-space-md">
                    @csrf

                    <fieldset>
                        <legend class="label">{{ __('Nòt ou') }}</legend>
                        <div class="flex items-center gap-1" @mouseleave="hover = 0">
                            @foreach ([1, 2, 3, 4, 5] as $n)
                                <label class="cursor-pointer rounded-lg p-1 focus-within:ring-2 focus-within:ring-primary-container"
                                       @mouseenter="hover = {{ $n }}">
                                    <input type="radio" name="rating" value="{{ $n }}" class="sr-only" required x-model.number="rating">
                                    <span class="sr-only">{{ __(':n sou 5', ['n' => $n]) }}</span>
                                    <span class="material-symbols-outlined text-[36px] transition-colors"
                                          :class="(hover || rating) >= {{ $n }} ? 'text-amber-400' : 'text-slate-300'"
                                          :style="(hover || rating) >= {{ $n }} ? 'font-variation-settings:\'FILL\' 1' : 'font-variation-settings:\'FILL\' 0'"
                                          aria-hidden="true">star</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('rating')" class="mt-1" />
                    </fieldset>

                    <div>
                        <label class="label" for="comment">{{ __('Kòmantè ou') }}</label>
                        <textarea id="comment" name="comment" rows="4" minlength="5" maxlength="500" required x-model="text"
                                  placeholder="{{ __('Ekri kèk mo sou eksperyans ou...') }}"
                                  class="input !py-3 !min-h-[110px]"></textarea>
                        <div class="flex justify-between gap-3 mt-1">
                            <x-input-error :messages="$errors->get('comment')" />
                            <span class="ml-auto font-body-sm text-body-sm text-on-surface-variant" x-text="text.length + '/500'"></span>
                        </div>
                    </div>

                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Sèlman premye non ou ak premye lèt dènye non ou ap parèt. Avi ou an parèt apre yon verifikasyon.') }}</p>

                    <div class="flex flex-wrap items-center gap-3">
                        <button class="btn-primary">{{ $mine ? __('Mete avi a ajou') : __('Voye avi a') }}</button>
                    </div>
                </form>

                @if ($mine)
                    <form method="POST" action="{{ route('reviews.destroy') }}" @submit="if (! confirm(@js(__('Efase avi ou an?')))) $event.preventDefault()">
                        @csrf @method('DELETE')
                        <button class="font-label-md text-label-md text-secondary hover:underline">{{ __('Efase avi mwen') }}</button>
                    </form>
                @endif
            </section>
        @else
            <section class="card !p-space-lg text-center space-y-space-sm">
                <span class="material-symbols-outlined text-[32px] text-primary-container" style="font-variation-settings:'FILL' 1">rate_review</span>
                <h2 class="font-title-lg text-title-lg">{{ __('Konekte pou bay avi ou') }}</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Sèlman moun ki gen yon kont ka bay yon avi.') }}</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center pt-1">
                    <a href="{{ route('login') }}" class="btn-primary">{{ __('Konekte') }}</a>
                    <a href="{{ route('register') }}" class="btn-secondary">{{ __('Kreye yon kont') }}</a>
                </div>
            </section>
        @endauth

        {{-- Liste des avis publiés --}}
        <section class="space-y-space-md" aria-label="{{ __('Avi moun yo') }}">
            @forelse ($reviews as $review)
                <article class="card !p-space-lg space-y-3" data-review>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-10 h-10 shrink-0 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-label-md text-label-md">{{ mb_strtoupper(mb_substr($review->displayName(), 0, 1)) }}</span>
                            <div class="min-w-0">
                                <p class="font-label-lg text-label-lg truncate">{{ $review->displayName() }}</p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $review->created_at->locale(app()->getLocale())->translatedFormat('j F Y') }}</p>
                            </div>
                        </div>
                        <x-star-rating :value="$review->rating" />
                    </div>
                    <p class="font-body-lg text-body-lg text-on-surface leading-relaxed break-words">{{ $review->comment }}</p>
                </article>
            @empty
                <div class="card !p-space-lg text-center">
                    <span class="material-symbols-outlined text-[32px] text-on-surface-variant">chat_bubble</span>
                    <p class="font-title-md text-title-md mt-1">{{ __('Poko gen avi ankò') }}</p>
                    <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Se ou ki ka premye a!') }}</p>
                </div>
            @endforelse

            {{ $reviews->links() }}
        </section>

        <div class="text-center">
            <a href="{{ route('home') }}" class="font-label-lg text-label-lg text-primary-container underline">{{ __('Retounen') }}</a>
        </div>
    </div>
</x-dynamic-component>
