<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · LajanAnsanm</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon-32.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .legal h1 { font-size: 1.6rem; font-weight: 800; color: #001058; margin: 0 0 .75rem; }
        .legal h2 { font-size: 1.2rem; font-weight: 700; color: #001058; margin: 1.75rem 0 .5rem; }
        .legal h3 { font-size: 1rem; font-weight: 600; margin: 1.1rem 0 .3rem; }
        .legal p { margin: .5rem 0; line-height: 1.7; }
        .legal ul { list-style: disc; padding-left: 1.4rem; margin: .4rem 0; }
        .legal li { margin: .2rem 0; line-height: 1.6; }
        .legal a { color: #0c2382; text-decoration: underline; }
        .legal hr { margin: 1.5rem 0; border-color: #e5e7eb; }
    </style>
</head>
<body class="bg-surface text-on-surface font-body-md antialiased min-h-screen">
    <header class="max-w-3xl mx-auto px-4 py-6 flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/logo-icon.png') }}" alt="" class="h-8 w-8">
            <span class="font-bold text-primary">LajanAnsanm</span>
        </a>
        <a href="{{ url('/') }}" class="text-sm text-primary underline">{{ app()->getLocale() === 'ht' ? 'Retounen' : 'Retour' }}</a>
    </header>
    <main class="max-w-3xl mx-auto px-4 pb-16">
        <article class="legal bg-surface-container-lowest rounded-2xl p-6 md:p-10 shadow-sm">
            {!! $html !!}
        </article>
    </main>
</body>
</html>
