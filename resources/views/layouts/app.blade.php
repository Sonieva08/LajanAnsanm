<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'LajanAnsanm') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LajanAnsanm">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-on-surface font-body-md text-body-md antialiased min-h-screen">

    @php
        $user = auth()->user();
        $initials = $user
            ? collect(preg_split('/\s+/', trim($user->name)))->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')
            : '?';
        $nav = [
            ['route' => 'dashboard',        'match' => 'dashboard',  'icon' => 'dashboard', 'label' => __('Akèy')],
            ['route' => 'tontines.index',   'match' => 'tontines.index|tontines.show|tontines.edit|tontines.*.*', 'icon' => 'savings', 'label' => __('Sòl')],
            ['route' => 'tontines.create',  'match' => 'tontines.create', 'icon' => 'add_circle', 'label' => __('Nouvo')],
            ['route' => 'profile.edit',     'match' => 'profile.*',  'icon' => 'manage_accounts', 'label' => __('Pwofil')],
        ];
    @endphp

    {{-- Barre du haut --}}
    <header class="fixed top-0 inset-x-0 z-40 bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)]"
            style="padding-top: env(safe-area-inset-top, 0px)">
        <div class="h-16 px-gutter max-w-5xl mx-auto flex items-center justify-between">
            <a href="{{ auth()->user() ? route('dashboard') : route('home') }}" class="flex items-center gap-space-sm">
                <img src="{{ asset('images/logo-icon-sm.png') }}" alt="LajanAnsanm" class="h-9 w-9">
                <span class="font-title-lg text-title-lg text-primary-container leading-tight">LajanAnsanm</span>
            </a>

            <div class="flex items-center gap-space-sm">
                <x-lang-switcher />

                {{-- Cloche de notifications --}}
                @php
                    $unreadNotificationsCount = $user ? $user->unreadNotifications()->count() : 0;
                @endphp
                <div x-data="headerNotifications({{ $unreadNotificationsCount }})" class="relative">
                    <button @click="toggleDropdown()" @click.outside="open = false"
                            class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container active:scale-95 transition relative text-on-surface-variant hover:text-primary-container"
                            aria-label="{{ __('Notifikasyon') }}">
                        <span class="material-symbols-outlined text-[24px]"
                              :class="count > 0 ? 'text-primary-container' : ''">notifications</span>
                        <template x-if="count > 0">
                            <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-error text-on-error font-bold text-[10px] flex items-center justify-center shadow-sm"
                                  x-text="count > 99 ? '99+' : count"></span>
                        </template>
                    </button>

                    <div x-show="open" x-transition x-cloak
                         class="absolute right-0 mt-2 w-80 sm:w-96 bg-surface-container-lowest rounded-2xl shadow-xl border border-slate-200 overflow-hidden z-50">
                        <div class="p-3 border-b border-slate-100 flex items-center justify-between bg-surface-container-low">
                            <div class="flex items-center gap-1.5">
                                <span class="font-title-md text-title-md font-bold text-on-surface">{{ __('Notifikasyon') }}</span>
                                <template x-if="count > 0">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-fixed text-primary-container" x-text="count + ' ' + '{{ __('nouvo') }}'"></span>
                                </template>
                            </div>
                            <template x-if="count > 0">
                                <button @click="markAllAsRead()" class="text-xs text-primary-container hover:underline font-semibold">
                                    {{ __('Make tout kòm li') }}
                                </button>
                            </template>
                        </div>

                        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                            <template x-if="loading">
                                <div class="p-4 text-center text-xs text-on-surface-variant">
                                    {{ __('Chajman...') }}
                                </div>
                            </template>
                            <template x-if="!loading && items.length === 0">
                                <div class="p-6 text-center text-on-surface-variant space-y-1">
                                    <span class="material-symbols-outlined text-[28px] opacity-40">notifications_off</span>
                                    <p class="text-xs font-medium">{{ __('Pa gen okenn nouvo notifikasyon') }}</p>
                                </div>
                            </template>
                            <template x-for="item in items" :key="item.id">
                                <a :href="item.action_url" @click="markAsRead(item.id)"
                                   class="p-3 flex items-start gap-3 hover:bg-surface-container/70 transition block">
                                    <div class="w-8 h-8 rounded-full bg-primary-fixed text-primary-container flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[18px]" x-text="item.icon || 'notifications'"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-on-surface truncate" x-text="item.title"></p>
                                        <p class="text-xs text-on-surface-variant line-clamp-2 mt-0.5" x-text="item.message"></p>
                                        <span class="text-[10px] text-on-surface-variant/70 mt-1 block" x-text="item.time_ago"></span>
                                    </div>
                                </a>
                            </template>
                        </div>

                        <div class="p-2.5 bg-surface-container-low border-t border-slate-100 text-center">
                            <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-primary-container hover:underline inline-flex items-center gap-1">
                                <span>{{ __('Wè tout notifikasyon yo') }}</span>
                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false"
                            class="w-11 h-11 flex items-center justify-center rounded-full active:scale-95 transition"
                            aria-label="{{ __('Pwofil') }}">
                        <span class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-label-sm text-label-sm">
                            {{ $initials }}
                        </span>
                    </button>

                    <div x-show="open" x-transition x-cloak
                         class="absolute right-0 mt-1 w-56 bg-surface-container-lowest rounded-xl shadow-lg border border-slate-200 p-space-xs">
                        <p class="px-space-sm py-space-sm font-label-md text-label-md truncate">{{ $user?->name }}</p>
                        <a href="{{ route('profile.edit') }}"
                           class="block px-space-sm py-space-sm rounded-lg hover:bg-surface-container">{{ __('Profile') }}</a>
                        <a href="{{ route('password.set') }}"
                           class="block px-space-sm py-space-sm rounded-lg hover:bg-surface-container">{{ __('Mete oswa chanje modpas ou') }}</a>
                        <a href="{{ route('subscription') }}" class="block px-space-sm py-space-sm rounded-lg hover:bg-surface-container">{{ __('Abònman mwen') }}</a>
                        <a href="{{ route('reviews.index') }}" class="block px-space-sm py-space-sm rounded-lg hover:bg-surface-container">{{ __('Bay avi ou') }}</a>
                        @if ($user?->is_owner)
                            <a href="{{ route('admin.overview') }}"
                               class="block px-space-sm py-space-sm rounded-lg hover:bg-surface-container text-primary-container font-label-md text-label-md">
                                {{ __('Konsòl pwopriyetè') }}
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full text-left px-space-sm py-space-sm rounded-lg text-secondary hover:bg-error-container">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Contenu --}}
    <main class="pt-20 pb-28 px-gutter max-w-5xl mx-auto" style="padding-top: calc(5rem + env(safe-area-inset-top, 0px))">
        @include('partials.maintenance-banner')
        @include('partials.announcements')
        @include('partials.subscription-banner')
        @isset($header)
            <div class="mb-space-lg">{{ $header }}</div>
        @endisset

        {{ $slot }}
    </main>

    {{-- Navigation du bas --}}
    <nav class="fixed bottom-0 inset-x-0 z-40 bg-surface/95 backdrop-blur-xl shadow-[0_-4px_16px_rgba(15,23,42,0.06)]"
         style="padding-bottom: env(safe-area-inset-bottom, 0px)">
        <div class="flex items-center justify-around h-16 px-space-xs max-w-lg mx-auto">
            @foreach ($nav as $item)
                @php $active = request()->routeIs(...explode('|', $item['match'])); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex flex-col items-center justify-center min-w-[64px] min-h-[48px] py-1 select-none group
                          {{ $active ? 'text-primary-container font-bold' : 'text-on-surface-variant hover:text-on-surface' }}">
                    <div class="flex items-center justify-center w-12 h-7 rounded-full transition
                                {{ $active ? 'bg-primary-fixed/70' : '' }}">
                        <span class="material-symbols-outlined text-[22px]"
                              style="font-variation-settings: 'FILL' {{ $active ? 1 : 0 }};">{{ $item['icon'] }}</span>
                    </div>
                    <span class="font-label-sm text-label-sm mt-0.5">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <script>
        function headerNotifications(initialCount) {
            return {
                count: initialCount,
                open: false,
                loading: false,
                items: [],
                init() {
                    setInterval(() => this.checkNew(), 45000);
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            this.checkNew();
                        }
                    });
                },
                toggleDropdown() {
                    this.open = !this.open;
                    if (this.open) {
                        this.fetchUnread();
                    }
                },
                async fetchUnread() {
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('notifications.unread') }}', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.count = data.count;
                            this.items = data.notifications;
                        }
                    } catch (e) {
                        console.error('Error fetching notifications:', e);
                    } finally {
                        this.loading = false;
                    }
                },
                async checkNew() {
                    try {
                        const res = await fetch('{{ route('notifications.unread') }}', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            const prev = this.count;
                            this.count = data.count;
                            this.items = data.notifications;

                            if (data.count > prev && 'Notification' in window && Notification.permission === 'granted') {
                                const newest = data.notifications[0];
                                if (newest) {
                                    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                                        navigator.serviceWorker.ready.then(reg => {
                                            reg.showNotification(newest.title, {
                                                body: newest.message,
                                                icon: '/images/apple-touch-icon.png',
                                                badge: '/images/logo-icon-sm.png',
                                                vibrate: [100, 50, 100],
                                                data: { url: newest.action_url || '{{ route('notifications.index') }}' }
                                            });
                                        });
                                    } else {
                                        new Notification(newest.title, {
                                            body: newest.message,
                                            icon: '/images/apple-touch-icon.png'
                                        });
                                    }
                                }
                            }
                        }
                    } catch (e) {
                        // background check failed silently
                    }
                },
                async markAsRead(id) {
                    try {
                        await fetch(`/notifications/${id}/read`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        });
                        this.count = Math.max(0, this.count - 1);
                        this.items = this.items.filter(i => i.id !== id);
                    } catch (e) {
                        console.error(e);
                    }
                },
                async markAllAsRead() {
                    try {
                        await fetch('{{ route('notifications.read-all') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        });
                        this.count = 0;
                        this.items = [];
                    } catch (e) {
                        console.error(e);
                    }
                }
            };
        }
    </script>
    @stack('scripts')
    @auth
    @include('partials.help-widget', ['audience' => 'creator'])
    @endauth
</body>
</html>
