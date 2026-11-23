<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name', 'LajanAnsanm') }}</title>
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
<body class="bg-surface text-on-surface antialiased min-h-screen flex flex-col items-center px-gutter py-space-lg">
    <div class="self-end"><x-lang-switcher /></div>

    <div class="w-full max-w-md text-center mt-space-lg">
        <picture>
            <source srcset="{{ asset('images/logo-full.webp') }}" type="image/webp">
            <img src="{{ asset('images/logo-full.png') }}" alt="LajanAnsanm — Jere Tontin ou, Bati Avenir ou" width="640" height="496" class="w-64 max-w-full h-auto mx-auto">
        </picture>
        <h1 class="sr-only">LajanAnsanm</h1>
    </div>

    <div class="w-full max-w-md card !p-space-lg mt-space-lg">{{ $slot }}</div>

    <p class="mt-space-lg inline-flex items-center gap-2 bg-surface-container-lowest shadow-sm rounded-full px-4 py-2 font-body-sm text-body-sm text-on-surface-variant">
        <span class="material-symbols-outlined text-[18px] text-primary-container">verified_user</span>
        {{ __('Sistèm sekirize pou kominote ayisyen an') }}
    </p>

    @include('partials.help-widget', ['audience' => 'guest'])
</body>
</html>
