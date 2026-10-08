<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'LajanAnsanm') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

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
            ['route' => 'tontines.index',   'match' => 'tontines.index|tontines.show|tontines.edit|tontines.*.*', 'icon' => 'savings', 'label' => __('Tontin')],
            ['route' => 'tontines.create',  'match' => 'tontines.create', 'icon' => 'add_circle', 'label' => __('Nouvo')],
            ['route' => 'profile.edit',     'match' => 'profile.*',  'icon' => 'manage_accounts', 'label' => __('Pwofil')],
        ];
    @endphp

    {{-- Barre du haut --}}
    <header class="fixed top-0 inset-x-0 z-40 bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)]"
            style="padding-top: env(safe-area-inset-top, 0px)">
        <div class="h-16 px-gutter max-w-5xl mx-auto flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-space-sm">
                <img src="{{ asset('images/logo.svg') }}" alt="LajanAnsanm" class="h-9 w-9">
                <span class="font-title-lg text-title-lg text-primary-container leading-tight">LajanAnsanm</span>
            </a>

            <div class="flex items-center gap-space-sm">
                <x-lang-switcher />

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
                        <a href="{{ route('subscription') }}" class="block px-space-sm py-space-sm rounded-lg hover:bg-surface-container">{{ __('Abònman mwen') }}</a>
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
</body>
</html>
