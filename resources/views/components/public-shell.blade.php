@props(['title', 'refresh' => null, 'status' => null])
{{-- Aucune dépendance à la base de données, à la session ou à l'authentification : ces pages doivent toujours s'afficher --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    @if ($refresh)<meta http-equiv="refresh" content="{{ $refresh }}">@endif
    <title>{{ $title }} · LajanAnsanm</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        @keyframes lajan-spin { to { transform: rotate(360deg); } }
        @keyframes lajan-spin-reverse { to { transform: rotate(-360deg); } }
    </style>
</head>
<body class="bg-surface text-on-surface font-body-md antialiased min-h-screen flex items-center justify-center px-margin py-space-lg">
    <main class="w-full max-w-[420px] sm:max-w-xl flex flex-col items-center gap-space-md">
        <div class="inline-flex items-center gap-space-sm bg-surface-container-lowest px-space-md py-1.5 rounded-full shadow-sm">
            <img src="{{ asset('images/logo.svg') }}" alt="LajanAnsanm" class="w-7 h-7 object-contain">
            <span class="font-title-md text-title-md tracking-tight text-primary">LajanAnsanm</span>
            @if ($status)
                <span class="w-1.5 h-1.5 rounded-full bg-outline-variant"></span>
                <span class="flex items-center gap-1.5">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-on-tertiary-container opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-on-tertiary-container"></span>
                    </span>
                    <span class="font-label-sm text-label-sm text-tertiary">{{ $status }}</span>
                </span>
            @endif
        </div>

        {{ $slot }}
    </main>
</body>
</html>
