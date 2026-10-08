@props(['active' => 'overview', 'title' => ''])
@php
    $pendingRequests = \App\Models\SubscriptionRequest::where('status', 'pending')->count();
    $nav = [
        ['overview', 'dashboard', __('Vi jeneral'), 'admin.overview', null],
        ['users', 'group', __('Itilizatè'), 'admin.users', null],
        ['plans', 'sell', __('Plan ak abònman'), 'admin.plans', null],
        ['requests', 'payments', __('Demann peman'), 'admin.requests', null],
        ['activity', 'history', __('Jounal aktivite'), 'admin.activity', null],
        ['announcements', 'campaign', __('Anons'), null, null],
        ['settings', 'settings', __('Paramèt'), 'admin.settings', null],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?: __('Konsòl pwopriyetè') }} · LajanAnsanm</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-on-surface antialiased">
<div class="min-h-screen lg:flex">

    {{-- Menu latéral (ordinateur) --}}
    <aside class="hidden lg:flex w-64 shrink-0 flex-col justify-between bg-surface-container-lowest border-r border-slate-200 p-space-md sticky top-0 h-screen">
        <div class="space-y-space-md">
            <a href="{{ route('admin.overview') }}" class="flex items-center gap-2 pb-space-sm border-b border-slate-100">
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-10 w-10">
                <div class="leading-tight">
                    <p class="font-title-md text-title-md text-primary-container">LajanAnsanm</p>
                    <p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ __('Konsòl pwopriyetè') }}</p>
                </div>
            </a>
            <nav class="space-y-1">
                @foreach ($nav as [$key, $icon, $label, $route, $_])
                    @if ($route)
                        <a href="{{ route($route) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-label-lg text-label-lg transition
                           {{ $active === $key ? 'bg-primary-container text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                            <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>{{ $label }}
                            @if ($key === 'requests' && $pendingRequests > 0)<span class="ml-auto bg-secondary text-on-secondary rounded-full text-[11px] px-2 py-0.5 font-bold">{{ $pendingRequests }}</span>@endif
                        </a>
                    @else
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-label-lg text-label-lg text-slate-400 cursor-not-allowed" title="{{ __('Byento') }}">
                            <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>{{ $label }}
                            <span class="ml-auto badge-muted !py-0 !px-2 text-[10px]">{{ __('Byento') }}</span>
                        </div>
                    @endif
                @endforeach
            </nav>
        </div>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 bg-primary-fixed/60 rounded-xl p-space-sm font-label-md text-label-md text-primary-container">
            <span class="material-symbols-outlined text-[18px]">open_in_new</span> {{ __('Aplikasyon piblik') }}
        </a>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="sticky top-0 z-20 bg-surface/90 backdrop-blur border-b border-slate-200">
            <div class="flex items-center justify-between gap-3 px-gutter lg:px-space-lg h-14">
                <a href="{{ route('admin.overview') }}" class="lg:hidden flex items-center gap-2">
                    <img src="{{ asset('images/logo.svg') }}" alt="" class="h-8 w-8">
                    <span class="font-title-md text-title-md text-primary-container">LajanAnsanm</span>
                </a>
                <span class="badge bg-primary-fixed text-primary-container"><span class="material-symbols-outlined text-[14px]">shield_person</span> {{ __('Mòd pwopriyetè') }}</span>
                <div class="flex items-center gap-3">
                    <x-lang-switcher />
                    <span class="hidden sm:block font-label-md text-label-md">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="w-9 h-9 rounded-full bg-surface-container-high flex items-center justify-center" title="{{ __('Log Out') }}"><span class="material-symbols-outlined text-[18px]">logout</span></button>
                    </form>
                </div>
            </div>
            {{-- Menu horizontal (téléphone) --}}
            <nav class="lg:hidden flex gap-1 overflow-x-auto px-gutter pb-2">
                @foreach ($nav as [$key, $icon, $label, $route, $_])
                    @if ($route)
                        <a href="{{ route($route) }}" class="shrink-0 px-3 py-1.5 rounded-full font-label-md text-label-md {{ $active === $key ? 'bg-primary-container text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }}">{{ $label }}</a>
                    @endif
                @endforeach
            </nav>
        </header>

        <main class="px-gutter lg:px-space-lg py-space-lg max-w-[1400px] mx-auto">
            @include('partials.maintenance-banner')
            @if (session('status'))<div class="alert-ok mb-space-md">{{ session('status') }}</div>@endif
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
