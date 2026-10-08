@props(['title', 'back', 'csv' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { margin: 14mm; }
        @media print { tr, .no-break { break-inside: avoid; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body class="bg-slate-100 print:bg-white text-slate-900 antialiased">
    <div class="print:hidden sticky top-0 z-10 bg-white shadow px-4 py-3 flex items-center justify-between gap-2">
        <a href="{{ $back }}" class="font-label-lg text-label-lg text-primary-container">← {{ __('Retounen') }}</a>
        <div class="flex gap-2">
            @if ($csv)
                @if (auth()->user()?->is_owner || \App\Support\Entitlements::for(auth()->user())->has('csv_export'))
                    <a href="{{ $csv }}" class="btn-secondary btn-sm">{{ __('CSV (Excel)') }}</a>
                @else
                    <a href="{{ route('plans') }}" class="btn-secondary btn-sm opacity-70">CSV · Premium</a>
                @endif
            @endif
            <button onclick="window.print()" class="btn-primary btn-sm">{{ __('PDF / Enprime') }}</button>
        </div>
    </div>

    <main class="max-w-3xl mx-auto bg-white shadow print:shadow-none my-4 print:my-0 p-6 sm:p-10 text-sm">
        <div class="flex items-center justify-between border-b-2 border-primary-container pb-3 mb-5">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-9 w-9">
                <div>
                    <p class="font-bold text-primary-container text-base leading-tight">LajanAnsanm</p>
                    <p class="text-xs text-slate-500">{{ __('Rapò tontin') }}</p>
                </div>
            </div>
            <p class="text-xs text-slate-500 text-right">
                {{ __('Jenere le') }} {{ now()->format('d/m/Y H:i') }}<br>
                {{ auth()->user()->name }}
            </p>
        </div>

        {{ $slot }}

        <div class="grid grid-cols-2 gap-10 mt-12 text-xs text-slate-500 no-break">
            <div class="border-t border-slate-400 pt-1">{{ __('Siyati administratè') }}</div>
            <div class="border-t border-slate-400 pt-1">{{ __('Siyati benefisyè') }}</div>
        </div>
        <p class="mt-6 text-[10px] text-slate-400 text-center">{{ __('Dokiman jenere pa LajanAnsanm') }}</p>
    </main>
</body>
</html>
