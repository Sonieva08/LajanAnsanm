@props(['title' => null, 'member' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}LajanAnsanm</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LajanAnsanm">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-on-surface font-body-md text-body-md antialiased min-h-screen">
    <header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)]" style="padding-top: env(safe-area-inset-top, 0px)">
        <div class="h-16 px-gutter max-w-xl mx-auto flex items-center justify-between">
            <span class="flex items-center gap-space-sm">
                <img src="{{ asset('images/logo-icon-sm.png') }}" alt="LajanAnsanm" class="h-9 w-9">
                <span class="font-title-lg text-title-lg text-primary-container leading-tight">LajanAnsanm</span>
            </span>
            <div class="flex items-center gap-space-sm">
                <x-lang-switcher />
                @if ($member)
                    <form method="POST" action="{{ route('portal.logout') }}">
                        @csrf
                        <button class="w-10 h-10 flex items-center justify-center rounded-full bg-surface-container-lowest shadow-sm" aria-label="{{ __('Kite espas la') }}">
                            <span class="material-symbols-outlined">logout</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-gutter py-space-lg space-y-space-md" style="padding-bottom: calc(2rem + env(safe-area-inset-bottom, 0px))">
        {{ $slot }}
    </main>
    @include('partials.help-widget', ['audience' => 'member'])
</body>
</html>
