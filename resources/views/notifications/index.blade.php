<x-app-layout>
    <div class="space-y-space-lg">

        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-space-sm">
            <div>
                <h1 class="font-headline-lg-mobile text-headline-lg-mobile flex items-center gap-2">
                    <span class="material-symbols-outlined text-[28px] text-primary-container" style="font-variation-settings: 'FILL' 1;">notifications</span>
                    {{ __('Notifikasyon') }}
                </h1>
                <p class="font-body-md text-body-md text-on-surface-variant mt-0.5">
                    {{ __('Rete okouran de tout aktivite manm yo ak deklarasyon peman.') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold bg-surface-container hover:bg-surface-container-high text-primary-container transition active:scale-95">
                            <span class="material-symbols-outlined text-[18px]">done_all</span>
                            {{ __('Make tout kòm li') }}
                        </button>
                    </form>
                @endif

                @if ($notifications->total() > $unreadCount)
                    <form method="POST" action="{{ route('notifications.clear-read') }}"
                          onsubmit="return confirm('{{ __('Èske ou sèten ou vle efase tout notifikasyon ki deja li yo?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface-variant transition active:scale-95"
                                title="{{ __('Efase sa ki deja li') }}">
                            <span class="material-symbols-outlined text-[18px]">delete_sweep</span>
                            <span class="hidden sm:inline">{{ __('Efase sa ki li yo') }}</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="bg-tertiary-fixed/40 text-on-tertiary-fixed-variant p-space-md rounded-xl flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        {{-- Carte Push Notifications Mobile --}}
        <div x-data="mobilePushCard()" class="p-space-md rounded-xl border border-slate-200 bg-surface-container-lowest shadow-sm">
            <div class="flex items-start justify-between gap-space-md">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                         :class="permission === 'granted' ? 'bg-emerald-100 text-emerald-700' : 'bg-primary-fixed text-primary-container'">
                        <span class="material-symbols-outlined text-[22px]" x-text="permission === 'granted' ? 'notifications_active' : 'mobile_friendly'"></span>
                    </div>
                    <div>
                        <h2 class="font-title-lg text-title-lg" x-text="permission === 'granted' ? '{{ __('Notifikasyon telefòn aktif') }}' : '{{ __('Resevwa alèt sou telefòn ou') }}'"></h2>
                        <p class="font-body-md text-body-md text-on-surface-variant mt-0.5"
                           x-text="permission === 'granted' 
                               ? '{{ __('Telefòn ou pare pou resevwa alèt otomatik lè yon manm deklare yon peman oswa konfime kòb li.') }}' 
                               : '{{ __('Aktive notifikasyon pou resevwa alèt enstantane tankou yon aplikasyon natif menm si ou pa sou sit la.') }}'">
                        </p>
                        <div x-show="msg" x-text="msg" class="mt-2 text-xs font-semibold text-emerald-600"></div>
                    </div>
                </div>

                <template x-if="supported && permission !== 'granted'">
                    <button @click="enableNotifications()" :disabled="loading"
                            class="shrink-0 px-space-md py-2 bg-primary-container hover:bg-primary-container/90 text-on-primary font-label-md text-label-md rounded-xl transition shadow-sm active:scale-95 disabled:opacity-60 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add_alert</span>
                        <span x-text="loading ? '{{ __('Nap verifye...') }}' : '{{ __('Aktive kounye a') }}'"></span>
                    </button>
                </template>

                <template x-if="supported && permission === 'granted'">
                    <button @click="testNotification()"
                            class="shrink-0 px-3 py-1.5 border border-slate-200 hover:bg-surface-container rounded-lg text-xs font-semibold text-on-surface-variant transition active:scale-95 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">touch_app</span>
                        <span>{{ __('Fè yon tès') }}</span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <a href="{{ route('notifications.index', ['filter' => 'all']) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-semibold transition {{ $currentFilter === 'all' ? 'bg-primary-container text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                {{ __('Tout') }} ({{ $notifications->total() }})
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-semibold transition flex items-center gap-1.5 {{ $currentFilter === 'unread' ? 'bg-primary-container text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                <span>{{ __('Ki poko li') }}</span>
                @if ($unreadCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-xs {{ $currentFilter === 'unread' ? 'bg-white text-primary-container' : 'bg-primary-container text-on-primary' }}">
                        {{ $unreadCount }}
                    </span>
                @endif
            </a>
        </div>

        {{-- Liste des notifications --}}
        @if ($notifications->isEmpty())
            <div class="text-center py-16 bg-surface-container-lowest rounded-xl border border-slate-200 space-y-3">
                <div class="w-16 h-16 mx-auto rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[32px]">notifications_off</span>
                </div>
                <h3 class="font-title-lg text-title-lg text-on-surface">{{ __('Pa gen notifikasyon pou kounye a') }}</h3>
                <p class="font-body-md text-body-md text-on-surface-variant max-w-sm mx-auto">
                    {{ $currentFilter === 'unread' ? __('Ou pa gen okenn nouvo notifikasyon ki poko li.') : __('Lè manm yo deklare peman oswa konfime resepsyon kòb yo, wap jwenn tout alèt yo isit la.') }}
                </p>
            </div>
        @else
            <div class="space-y-space-xs">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $isUnread = is_null($notification->read_at);
                        $locale = app()->getLocale();
                        $title = $locale === 'fr' ? ($data['title_fr'] ?? $data['title'] ?? 'Notification') : ($data['title'] ?? 'Notifikasyon');
                        $message = $locale === 'fr' ? ($data['message_fr'] ?? $data['message'] ?? '') : ($data['message'] ?? '');
                        $icon = $data['icon'] ?? 'notifications';
                        $actionUrl = $data['action_url'] ?? null;
                    @endphp

                    <div class="p-space-md rounded-xl border transition flex items-start justify-between gap-space-md
                                {{ $isUnread ? 'bg-primary-fixed/25 border-primary-container/40 shadow-sm' : 'bg-surface-container-lowest border-slate-200' }}">
                        <div class="flex items-start gap-3 min-w-0">
                            {{-- Icône --}}
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0
                                        {{ $isUnread ? 'bg-primary-container text-on-primary' : 'bg-surface-container text-on-surface-variant' }}">
                                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' {{ $isUnread ? 1 : 0 }};">{{ $icon }}</span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-title-md text-title-md truncate {{ $isUnread ? 'text-primary-container font-bold' : 'text-on-surface font-semibold' }}">
                                        {{ $title }}
                                    </h4>
                                    @if ($isUnread)
                                        <span class="w-2 h-2 rounded-full bg-primary-container shrink-0"></span>
                                    @endif
                                </div>

                                <p class="font-body-md text-body-md text-on-surface-variant mt-0.5 break-words">
                                    {{ $message }}
                                </p>

                                <div class="flex items-center gap-space-sm mt-2 font-label-sm text-label-sm text-on-surface-variant/80">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">schedule</span>
                                        {{ $notification->created_at->locale($locale)->diffForHumans() }}
                                    </span>

                                    @if ($actionUrl)
                                        <span>•</span>
                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="redirect" value="1">
                                            <button type="submit" class="text-primary-container hover:underline font-semibold flex items-center gap-0.5">
                                                <span>{{ __('Wè detay yo') }}</span>
                                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Actions individuelles --}}
                        <div class="flex items-center gap-1 shrink-0">
                            @if ($isUnread)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:text-primary-container hover:bg-surface-container transition"
                                            title="{{ __('Make kòm li') }}">
                                        <span class="material-symbols-outlined text-[18px]">done</span>
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}"
                                  onsubmit="return confirm('{{ __('Efase notifikasyon sa a?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:text-secondary hover:bg-error-container/30 transition"
                                        title="{{ __('Efase') }}">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-space-md">
                {{ $notifications->links() }}
            </div>
        @endif

    </div>

    @push('scripts')
    <script>
        function mobilePushCard() {
            return {
                supported: 'Notification' in window && 'serviceWorker' in navigator,
                permission: 'Notification' in window ? Notification.permission : 'denied',
                loading: false,
                msg: '',
                async enableNotifications() {
                    if (!this.supported) {
                        alert('{{ __('Navigatè sa a pa sipòte notifikasyon push.') }}');
                        return;
                    }
                    this.loading = true;
                    try {
                        const perm = await Notification.requestPermission();
                        this.permission = perm;
                        if (perm === 'granted') {
                            this.msg = '{{ __('Notifikasyon yo aktive avèk siksè!') }}';
                            this.testNotification();
                        } else if (perm === 'denied') {
                            alert('{{ __('Notifikasyon yo bloke nan paramèt navigatè ou a.') }}');
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                },
                testNotification() {
                    if (this.permission !== 'granted') return;
                    if ('serviceWorker' in navigator) {
                        navigator.serviceWorker.ready.then(reg => {
                            reg.showNotification('LajanAnsanm · Tès Notifikasyon', {
                                body: '{{ __('Sistèm alèt la ap fonksyone byen sou telefòn ou!') }}',
                                icon: '/images/apple-touch-icon.png',
                                badge: '/images/logo-icon-sm.png',
                                vibrate: [100, 50, 100],
                                data: { url: '{{ route('notifications.index') }}' }
                            });
                        });
                    } else {
                        new Notification('LajanAnsanm · Tès Notifikasyon', {
                            body: '{{ __('Sistèm alèt la ap fonksyone byen sou telefòn ou!') }}',
                            icon: '/images/apple-touch-icon.png'
                        });
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
