@props(['title' => null, 'description' => null])
@php $wa = preg_replace('/\D/', '', (string) config('lajanansanm.support_whatsapp')); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="{{ $description ?? __('Jere sòl ou yo, kotizasyon yo ak vèsman yo nan yon sèl kote.') }}">
    <title>LajanAnsanm{{ $title ? ' · '.$title : '' }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-on-surface font-body-md text-body-md antialiased min-h-screen overflow-x-hidden">

{{-- En-tête --}}
<header class="fixed top-0 inset-x-0 z-50 bg-surface/85 backdrop-blur-xl border-b border-white/60" style="padding-top: env(safe-area-inset-top, 0px)">
    <div class="h-20 max-w-7xl mx-auto px-4 md:px-8 flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 min-w-0">
            <img src="{{ asset('images/logo-icon-sm.png') }}" alt="" width="40" height="40" class="h-10 w-10 shrink-0">
            <span class="font-headline-md text-headline-md text-primary font-extrabold truncate">LajanAnsanm</span>
        </a>
        <nav class="hidden md:flex items-center gap-7 font-label-lg text-label-lg text-on-surface-variant">
            <a href="{{ url('/') }}#kijan" class="hover:text-primary transition-colors">{{ __('Kouman li mache') }}</a>
            <a href="{{ url('/') }}#pou-ki-moun" class="hover:text-primary transition-colors">{{ __('Pou ki moun') }}</a>
            <a href="{{ route('reviews.index') }}" class="hover:text-primary transition-colors">{{ __('Avi') }}</a>
            <a href="{{ url('/') }}#kesyon" class="hover:text-primary transition-colors">{{ __('Kesyon') }}</a>
            <a href="#kontak" class="hover:text-primary transition-colors">{{ __('Kontak') }}</a>
        </nav>
        <div class="flex items-center gap-2.5 shrink-0">
            <x-lang-switcher />
            @auth
                <a href="{{ route('dashboard') }}" class="min-h-[44px] px-4 sm:px-5 rounded-xl bg-primary text-on-primary font-label-lg text-label-lg shadow-md hover:shadow-lg flex items-center transition-all">{{ __('Akèy') }}</a>
            @else
                <a href="{{ route('login') }}" class="hidden sm:flex min-h-[44px] px-5 rounded-xl text-primary font-label-lg text-label-lg hover:bg-primary-fixed/60 transition-colors items-center">{{ __('Konekte') }}</a>
                <a href="{{ route('register') }}" class="min-h-[44px] px-4 sm:px-5 rounded-xl bg-primary text-on-primary font-label-lg text-label-lg shadow-md hover:shadow-lg flex items-center transition-all">{{ __('Kreye yon kont') }}</a>
            @endauth
        </div>
    </div>
</header>

<main id="top" class="pt-20">
    {{ $slot }}
</main>

{{-- Pye paj --}}
<footer id="kontak" class="bg-surface-container-low py-10">
    <div class="max-w-7xl mx-auto px-4 md:px-8 flex flex-col md:flex-row items-center justify-between gap-4 font-body-sm text-body-sm text-on-surface-variant">
        <div class="flex flex-wrap items-center justify-center gap-6">
            <a href="{{ route('reviews.index') }}" class="hover:text-primary">{{ __('Avi moun yo') }}</a>
            <a href="{{ route('privacy') }}" class="hover:text-primary">{{ __('Politik konfidansyalite') }}</a>
            <a href="{{ route('terms') }}" class="hover:text-primary">{{ __('Kondisyon Sèvis') }}</a>
            @if ($wa !== '')
                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 text-primary font-label-md">
                    <span class="material-symbols-outlined text-[18px]">chat</span>{{ __('Kontakte sipò sou WhatsApp') }}
                </a>
            @endif
        </div>
        <span>© LajanAnsanm {{ date('Y') }}</span>
    </div>
</footer>

{{-- Bouton WhatsApp flottant : seulement si un numéro de support est configuré --}}
@if ($wa !== '')
    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener noreferrer"
       aria-label="{{ __('Kontakte sipò sou WhatsApp') }}"
       class="fixed right-4 z-40 w-14 h-14 rounded-full bg-tertiary-container text-on-primary shadow-xl hover:scale-105 active:scale-95 transition flex items-center justify-center"
       style="bottom: calc(1rem + env(safe-area-inset-bottom, 0px))">
        <span class="material-symbols-outlined text-[28px]" style="font-variation-settings:'FILL' 1">chat</span>
    </a>
@endif
</body>
</html>
