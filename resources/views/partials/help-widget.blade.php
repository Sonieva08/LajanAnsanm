@props(['audience' => 'creator'])
@php
    $faqs = \App\Support\HelpCenter::for($audience);
    $wa = preg_replace('/\D/', '', (string) config('lajanansanm.support_whatsapp'));
    $payload = [
        'audience' => $audience,
        'csrf' => csrf_token(),
        'logUrl' => route('help.log'),
        'askUrl' => route('help.ask'),
        'faqs' => $faqs,
        'wa' => $wa !== '' ? 'https://wa.me/' . $wa : null,
        'i18n' => [
            'user' => __('Ou'),
            'bot' => __('Jazon'),
            'searching' => __('M ap fè rechèch sou aplikasyon an...'),
            'noAnswer' => __('Mwen pa gen yon repons dirèk pou kesyon sa a. Gade kesyon sa yo, oswa kontakte sipò a.'),
            'related' => __('Sijè ki ka enterese w'),
            'notHelped' => __('Sa a pa ede m'),
            'noResult' => __('Pa gen kesyon pare pou rechèch sa a.'),
            'askAiBtn' => __('Mande Asistan an reponn ou dirèkteman'),
            'accountData' => __('Enfòmasyon sou kont ou'),
            'welcomeLine1' => __('Bonjou ! Mwen se Jazon. 🤖 Mwen la pou reponn tout kesyon ou sou platfòm nan.'),
            'welcomeLine2' => __('Ou ka mande m nenpòt bagay : jere sòl, peman, manm, kalandriye, plan, rapò... Tape kesyon ou anba a !'),
            'welcomeActions' => [],
        ],
    ];
    $fabPos = match ($audience) {
        'member', 'guest', 'admin' => 'bottom-6',
        default => 'bottom-24',
    };
@endphp

<div id="lajan-help" data-help-widget>
    <button type="button" id="help-fab" aria-label="{{ __('Mande èd') }}"
            class="fixed {{ $fabPos }} right-4 z-40 w-12 h-12 rounded-full bg-primary-container text-on-primary shadow-xl flex items-center justify-center active:scale-95 transition hover:brightness-110">
        <span class="material-symbols-outlined text-[24px]">smart_toy</span>
    </button>

    <div id="help-backdrop" class="hidden fixed inset-0 z-50 bg-inverse-surface/45 backdrop-blur-[2px]"></div>

    <section id="help-sheet" role="dialog" aria-modal="true" aria-labelledby="help-title"
             class="hidden fixed inset-x-0 bottom-0 z-50 h-[88vh] max-h-[88vh] bg-surface-container-lowest rounded-t-3xl shadow-2xl flex-col overflow-hidden max-w-2xl mx-auto border border-surface-container">
        <div class="w-full pt-3 pb-1 flex justify-center"><div class="w-10 h-1.5 rounded-full bg-surface-container-highest"></div></div>

        <div class="px-space-md pt-space-xs pb-space-sm flex items-start justify-between">
            <div class="pr-space-sm">
                <div class="flex items-center gap-2">
                    <h2 id="help-title" class="font-headline-md text-headline-md text-on-surface">{{ __('Ki jan nou ka ede w?') }}</h2>
                    <span class="inline-flex items-center gap-1 font-label-badge text-label-badge bg-primary/10 text-primary px-2 py-0.5 rounded-full font-semibold">
                        <span class="material-symbols-outlined text-[13px]">auto_awesome</span> {{ __('AI') }}
                    </span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Poze nenpòt kesyon sou aplikasyon an, asistan an ap reponn ou san tann.') }}</p>
            </div>
            <button type="button" id="help-close" aria-label="{{ __('Fèmen') }}" class="w-9 h-9 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center shrink-0 hover:bg-surface-container-highest">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        {{-- Vue 1 : liste & rechèch --}}
        <div id="help-listview" class="min-h-0 flex-1 overflow-y-auto px-space-md pb-space-lg flex flex-col gap-space-md">
            <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-3.5 text-on-surface-variant text-[20px] pointer-events-none">search</span>
                <input id="help-search" type="search" autocomplete="off" placeholder="{{ __('Chèche oswa tape yon kesyon dirèk...') }}"
                       class="w-full h-11 pl-10 pr-4 rounded-xl bg-surface-container-low text-on-surface font-body-md text-body-md border-0 focus:ring-2 focus:ring-primary-container">
            </div>
            <div class="flex items-center justify-between">
                <span class="font-title-sm text-title-sm">{{ __('Gid ak Kesyon souvan') }}</span>
                <span class="font-label-badge text-label-badge bg-surface-container-high text-on-surface-variant px-2 py-0.5 rounded-full">{{ count($faqs) }}</span>
            </div>
            <div id="help-list" class="flex flex-col gap-2"></div>
            <div id="help-empty-wrap" class="hidden flex-col items-center gap-3 py-space-md">
                <p id="help-empty" class="text-center text-on-surface-variant font-body-md text-body-md"></p>
                <button type="button" id="help-ask-custom" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-container text-on-primary font-label-md text-label-md shadow-sm active:scale-95 transition">
                    <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                    <span id="help-ask-custom-text">{{ __('Poze Asistan an kesyon sa a') }}</span>
                </button>
            </div>
        </div>

        {{-- Vue 2 : konvèsasyon entèlijan --}}
        <div id="help-chatview" class="hidden flex-col min-h-0 flex-1">
            <div class="px-space-md pb-2 flex items-center justify-between">
                <button type="button" id="help-back" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container">
                    <span class="material-symbols-outlined text-[20px]">menu_book</span> {{ __('FAQ') }}
                </button>
                <span class="inline-flex items-center gap-1 font-label-sm text-label-sm text-on-surface-variant">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span> {{ __('Otonòm') }}
                </span>
            </div>
            <div id="help-thread" class="min-h-0 overflow-y-auto overscroll-contain px-space-md flex flex-col gap-space-md flex-1 pb-space-md"></div>
            <form id="help-form" class="px-space-md py-2 flex items-center gap-2 bg-surface-container-lowest border-t border-surface-container">
                <input id="help-input" type="text" maxlength="250" autocomplete="off" placeholder="{{ __('Tape nenpòt kesyon pou asistan an...') }}"
                       class="flex-1 h-11 px-4 rounded-full bg-surface-container-low text-on-surface font-body-md text-body-md border-0 focus:ring-2 focus:ring-primary-container">
                <button type="submit" aria-label="{{ __('Voye') }}" class="w-11 h-11 rounded-full bg-primary-container text-on-primary flex items-center justify-center shrink-0 active:scale-95 transition">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                </button>
            </form>
        </div>

        <div class="px-space-md pt-2 pb-space-md space-y-2 border-t border-surface-container">
            @if ($wa !== '')
                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener noreferrer"
                   class="w-full h-11 rounded-xl bg-[#25D366] text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-sm hover:brightness-105">
                    <span class="material-symbols-outlined text-[20px]">chat</span> {{ $audience === 'member' ? __('Pwoblèm teknik? Sipò sou WhatsApp') : __('Pale ak sipò sou WhatsApp') }}
                </a>
            @endif
            <p class="text-center font-body-sm text-body-sm text-on-surface-variant">
                {{ $audience === 'member' ? __('Pou kesyon sou peman ou, pale ak òganizatè a.') : __('Jazon reponn ou sou tout fonksyonalite ak règleman yo.') }}
            </p>
        </div>
    </section>

    <script type="application/json" id="help-data">{!! json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <script>
        (function () {
            var root = document.getElementById('lajan-help');
            if (!root) return;
            var D = JSON.parse(document.getElementById('help-data').textContent);
            var $ = function (s) { return root.querySelector(s); };
            var fab = $('#help-fab'), back = $('#help-backdrop'), sheet = $('#help-sheet'),
                listEl = $('#help-list'), emptyWrap = $('#help-empty-wrap'), emptyEl = $('#help-empty'),
                askCustomBtn = $('#help-ask-custom'), search = $('#help-search'),
                listView = $('#help-listview'), chatView = $('#help-chatview'), thread = $('#help-thread'),
                form = $('#help-form'), input = $('#help-input');

            var loadingEl = null;

            function norm(s) { return String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); }
            function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }

            function log(type, faq, q) {
                try {
                    fetch(D.logUrl, {
                        method: 'POST', keepalive: true,
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': D.csrf },
                        body: JSON.stringify({ audience: D.audience, type: type, faq_id: faq || null, query: q || null })
                    }).catch(function () {});
                } catch (e) {}
            }

            function setOpen(open) {
                sheet.classList.toggle('hidden', !open); sheet.classList.toggle('flex', open);
                back.classList.toggle('hidden', !open);
                fab.classList.toggle('hidden', open);
                if (open) { openToChat(); log('open'); } else { fab.focus(); }
            }
            function openToChat() {
                // Ouvrir directement en mode chat avec message de bienvenue IA
                listView.classList.add('hidden'); listView.classList.remove('flex');
                chatView.classList.remove('hidden'); chatView.classList.add('flex');
                if (thread.children.length === 0) { showWelcomeMessage(); }
                setTimeout(function () { input.focus(); }, 50);
            }
            function showWelcomeMessage() {
                botBubble([
                    D.i18n.welcomeLine1,
                    D.i18n.welcomeLine2,
                ], { actions: [], related: ['add_member', 'order', 'payout', 'plans'], is_personalized: false }, false);
            }
            function showList() {
                chatView.classList.add('hidden'); chatView.classList.remove('flex');
                listView.classList.remove('hidden'); search.value = ''; renderList('');
            }
            function showChat() {
                listView.classList.add('hidden');
                chatView.classList.remove('hidden'); chatView.classList.add('flex');
                setTimeout(function () { input.focus(); }, 50);
            }

            function renderList(filter) {
                listEl.textContent = ''; var f = norm(filter).split(/\s+/).filter(function (t) { return t.length >= 2; }); var n = 0;
                D.faqs.forEach(function (faq) {
                    var hay = norm(faq.q + ' ' + (faq.kw || ''));
                    if (f.length && !f.every(function (t) { return hay.indexOf(t) !== -1; })) return;
                    n++;
                    var b = el('button', 'w-full text-left p-3.5 rounded-xl bg-surface-container-low hover:bg-surface-container flex items-center justify-between gap-2 transition');
                    b.type = 'button';
                    var left = el('span', 'flex items-center gap-3 min-w-0');
                    var ic = el('span', 'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ' + (faq.tone === 'alert' ? 'bg-error-container text-error' : 'bg-secondary-container text-primary-container'));
                    ic.appendChild(el('span', 'material-symbols-outlined text-[18px]', faq.icon || 'help')); left.appendChild(ic);
                    left.appendChild(el('span', 'font-body-md text-body-md text-on-surface font-medium truncate', faq.q));
                    b.appendChild(left); b.appendChild(el('span', 'material-symbols-outlined text-[20px] text-on-surface-variant shrink-0', 'chevron_right'));
                    b.addEventListener('click', function () { thread.textContent = ''; askStatic(faq); });
                    listEl.appendChild(b);
                });

                if (n === 0 && filter.trim().length > 0) {
                    emptyWrap.classList.remove('hidden'); emptyWrap.classList.add('flex');
                    emptyEl.textContent = D.i18n.noResult;
                    $('#help-ask-custom-text').textContent = D.i18n.askAiBtn + ' (« ' + filter.trim() + ' »)';
                } else {
                    emptyWrap.classList.add('hidden'); emptyWrap.classList.remove('flex');
                }
            }

            function userBubble(text) {
                var w = el('div', 'flex flex-col items-end gap-1 pl-8');
                w.appendChild(el('div', 'bg-primary-container text-on-primary rounded-2xl rounded-br-none px-4 py-3 shadow-sm max-w-[90%] font-body-md text-body-md', text));
                thread.appendChild(w);
                thread.scrollTop = thread.scrollHeight;
            }

            function showLoadingBubble() {
                removeLoadingBubble();
                var w = el('div', 'flex items-start gap-space-sm pr-2');
                var av = el('div', 'w-9 h-9 rounded-xl bg-primary flex items-center justify-center shrink-0');
                av.appendChild(el('span', 'material-symbols-outlined text-on-primary text-[20px] animate-spin', 'progress_activity')); w.appendChild(av);
                var col = el('div', 'flex flex-col gap-space-sm flex-1 min-w-0');
                var card = el('div', 'bg-surface-container-low rounded-2xl p-space-md space-y-1');
                card.appendChild(el('span', 'font-label-sm text-label-sm text-primary font-semibold block', D.i18n.bot));
                var p = el('p', 'font-body-sm text-body-sm text-on-surface-variant italic', D.i18n.searching);
                card.appendChild(p); col.appendChild(card); w.appendChild(col);
                loadingEl = w;
                thread.appendChild(w);
                thread.scrollTop = thread.scrollHeight;
            }

            function removeLoadingBubble() {
                if (loadingEl && loadingEl.parentNode) {
                    loadingEl.parentNode.removeChild(loadingEl);
                }
                loadingEl = null;
            }

            function chip(text, icon, onClick) {
                var b = el('button', 'bg-surface-container-high hover:bg-surface-container-highest text-primary font-label-md text-label-md px-3 py-1.5 rounded-full inline-flex items-center gap-1 text-left transition');
                b.type = 'button'; if (icon) b.appendChild(el('span', 'material-symbols-outlined text-[16px]', icon)); b.appendChild(el('span', null, text));
                b.addEventListener('click', onClick); return b;
            }

            function formatParagraph(card, text) {
                var p = el('p', 'font-body-md text-body-md text-on-surface leading-relaxed');
                // Formatage simple du markdown **gras**
                var parts = String(text || '').split(/(\*\*.*?\*\*)/g);
                parts.forEach(function (pt) {
                    if (pt.startsWith('**') && pt.endsWith('**')) {
                        var strong = el('strong', 'font-semibold text-on-surface', pt.slice(2, -2));
                        p.appendChild(strong);
                    } else if (pt.length > 0) {
                        p.appendChild(document.createTextNode(pt));
                    }
                });
                card.appendChild(p);
            }

            function botBubble(paragraphs, data, isMiss) {
                data = data || {};
                var w = el('div', 'flex items-start gap-space-sm pr-2');
                var av = el('div', 'w-9 h-9 rounded-xl bg-primary flex items-center justify-center shrink-0 shadow-sm');
                av.appendChild(el('span', 'material-symbols-outlined text-on-primary text-[20px]', 'smart_toy')); w.appendChild(av);
                var col = el('div', 'flex flex-col gap-space-sm flex-1 min-w-0');
                var card = el('div', 'bg-surface-container-low rounded-2xl rounded-tl-none p-space-md space-y-2 border border-surface-container/60 shadow-sm');

                var headerRow = el('div', 'flex items-center justify-between gap-2');
                headerRow.appendChild(el('span', 'font-label-md text-label-md text-primary font-semibold block', D.i18n.bot));

                if (data.is_personalized) {
                    var badge = el('span', 'inline-flex items-center gap-1 font-label-badge text-label-badge bg-primary/10 text-primary px-2.5 py-0.5 rounded-full font-semibold');
                    badge.appendChild(el('span', 'material-symbols-outlined text-[13px]', 'auto_awesome'));
                    badge.appendChild(el('span', null, D.i18n.accountData));
                    headerRow.appendChild(badge);
                }
                card.appendChild(headerRow);

                (paragraphs || []).forEach(function (p) {
                    formatParagraph(card, p);
                });
                col.appendChild(card);

                if (data.actions && data.actions.length) {
                    var acts = el('div', 'flex flex-wrap gap-1.5 pt-1');
                    data.actions.forEach(function (a) {
                        var l = el('a', 'bg-secondary-container hover:bg-secondary text-on-secondary-fixed-variant font-label-md text-label-md px-3.5 py-1.5 rounded-full inline-flex items-center gap-1 font-semibold transition shadow-sm', a[0]);
                        l.href = a[1];
                        l.insertBefore(el('span', 'material-symbols-outlined text-[16px]', 'arrow_forward'), l.firstChild);
                        acts.appendChild(l);
                    });
                    col.appendChild(acts);
                }

                var rel = [];
                if (data.related && data.related.length) {
                    data.related.forEach(function (id) {
                        var r = D.faqs.filter(function (x) { return x.id === id; })[0];
                        if (r) rel.push(r);
                    });
                }
                if (isMiss && !rel.length) {
                    rel = D.faqs.slice(0, 3);
                }

                if (rel.length) {
                    col.appendChild(el('p', 'font-label-badge text-label-badge text-on-surface-variant uppercase tracking-wider pt-1', D.i18n.related));
                    var chips = el('div', 'flex flex-wrap gap-1.5');
                    rel.forEach(function (r) {
                        chips.appendChild(chip(r.q, r.icon || 'help', function () {
                            askStatic(r);
                        }));
                    });
                    col.appendChild(chips);
                }

                if (D.wa) {
                    var esc = el('a', 'inline-flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant pt-1 hover:text-on-surface', D.i18n.notHelped + ' · ');
                    esc.href = D.wa; esc.target = '_blank'; esc.rel = 'noopener noreferrer';
                    esc.appendChild(el('strong', 'text-on-surface font-semibold underline decoration-dotted', 'WhatsApp'));
                    col.appendChild(esc);
                }

                w.appendChild(col);
                thread.appendChild(w);
                thread.scrollTop = thread.scrollHeight;
            }

            function askStatic(faq) {
                showChat();
                userBubble(faq.q);
                botBubble(faq.a, faq, false);
                log('open', faq.id, faq.q);
            }

            function askAi(queryText) {
                var q = queryText.trim();
                if (!q) return;

                showChat();
                userBubble(q);
                showLoadingBubble();

                fetch(D.askUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': D.csrf
                    },
                    body: JSON.stringify({
                        audience: D.audience,
                        query: q
                    })
                })
                .then(function (res) {
                    if (!res.ok) throw new Error('Erè rezo: ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    removeLoadingBubble();
                    botBubble(data.answer || data.text || [data.message], data, !data.matched_id && !data.is_personalized);
                })
                .catch(function (err) {
                    removeLoadingBubble();
                    var f = best(q);
                    if (f) {
                        botBubble(f.a, f, false);
                        log('search', f.id, q);
                    } else {
                        botBubble([D.i18n.noAnswer], null, true);
                        log('miss', null, q);
                    }
                });
            }

            function best(q) {
                var toks = norm(q).split(/\s+/).filter(function (t) { return t.length >= 3; });
                if (!toks.length) return null;
                var top = null, score = 0;
                D.faqs.forEach(function (f) {
                    var hay = norm(f.q + ' ' + (f.kw || '')), s = 0;
                    toks.forEach(function (t) { if (hay.indexOf(t) !== -1) s++; });
                    if (s > score) { score = s; top = f; }
                });
                return score >= 1 ? top : null;
            }

            fab.addEventListener('click', function () { setOpen(true); });
            $('#help-close').addEventListener('click', function () { setOpen(false); });
            back.addEventListener('click', function () { setOpen(false); });
            $('#help-back').addEventListener('click', function () { showList(); setTimeout(function () { search.focus(); }, 50); });

            search.addEventListener('input', function () { renderList(search.value); });
            search.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var q = search.value.trim();
                    if (!q) return;
                    askAi(q);
                }
            });

            if (askCustomBtn) {
                askCustomBtn.addEventListener('click', function () {
                    var q = search.value.trim();
                    if (q) askAi(q);
                });
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var q = input.value.trim();
                if (!q) return;
                input.value = '';
                askAi(q);
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !sheet.classList.contains('hidden')) setOpen(false);
            });
        })();
    </script>
</div>
