<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name', 'LajanAnsanm') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-on-surface antialiased min-h-screen flex flex-col items-center px-gutter py-space-lg">
    <div class="self-end"><x-lang-switcher /></div>

    <div class="w-full max-w-md text-center mt-space-lg">
        <img src="{{ asset('images/logo.svg') }}" alt="LajanAnsanm" class="h-24 w-24 mx-auto">
        <h1 class="font-headline-xl-mobile text-headline-xl-mobile text-primary-container mt-space-md">LajanAnsanm</h1>
        <p class="text-on-surface-variant mt-1">{{ __('Lajan ansanm, pi fò ansanm') }}</p>
    </div>

    <div class="w-full max-w-md card !p-space-lg mt-space-lg">{{ $slot }}</div>

    <p class="mt-space-lg inline-flex items-center gap-2 bg-surface-container-lowest shadow-sm rounded-full px-4 py-2 font-body-sm text-body-sm text-on-surface-variant">
        <span class="material-symbols-outlined text-[18px] text-primary-container">verified_user</span>
        {{ __('Sistèm sekirize pou kominote ayisyen an') }}
    </p>
</body>
</html>
