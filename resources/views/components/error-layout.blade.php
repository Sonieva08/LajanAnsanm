@props(['code', 'icon' => 'error', 'title', 'message', 'tone' => 'blue'])
@php
    $waDigits = preg_replace('/\D/', '', (string) config('lajanansanm.support_whatsapp'));
@endphp
<x-public-shell :title="$title">
    <div class="relative w-full bg-surface-container-lowest rounded-2xl shadow-sm overflow-hidden p-space-lg flex flex-col items-center text-center">
        <div aria-hidden="true" class="absolute -top-3 select-none pointer-events-none font-headline-xl text-[100px] sm:text-[130px] leading-none font-black tracking-tighter {{ $tone === 'red' ? 'text-error-container/70' : 'text-surface-container-highest' }}">{{ $code }}</div>
        <div aria-hidden="true" class="absolute -top-12 w-48 h-48 bg-surface-container-high rounded-full blur-3xl opacity-60 pointer-events-none"></div>

        <div class="relative z-10 mt-space-xl mb-space-md">
            <div class="w-20 h-20 rounded-full flex items-center justify-center shadow-sm {{ $tone === 'red' ? 'bg-error-container text-secondary' : 'bg-surface-container-low text-primary-container' }}">
                <span class="material-symbols-outlined text-[36px]" style="font-variation-settings: 'FILL' 1;">{{ $icon }}</span>
            </div>
        </div>

        @isset($pill)<div class="relative z-10 mb-space-sm">{{ $pill }}</div>@endisset

        <div class="relative z-10 flex flex-col items-center">
            <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface tracking-tight mb-space-xs">{{ $title }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed max-w-[300px]">{{ $message }}</p>
        </div>

        @isset($extra)<div class="relative z-10 w-full mt-space-md">{{ $extra }}</div>@endisset

        <div class="relative z-10 w-full flex flex-col sm:flex-row gap-space-xs mt-space-lg">{{ $slot }}</div>

        @if (request()->secure())
            <div class="relative z-10 mt-space-md flex items-center gap-1.5 text-on-surface-variant font-label-sm text-label-sm">
                <span class="material-symbols-outlined text-[15px] text-primary" style="font-variation-settings: 'FILL' 1;">verified_user</span>
                <span>{{ __('Koneksyon sekirize (HTTPS)') }}</span>
            </div>
        @endif

        @if ($waDigits !== '')
            <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener noreferrer"
               class="relative z-10 mt-space-md inline-flex items-center gap-2 bg-surface-container-highest px-space-md py-2 rounded-full font-label-md text-label-md text-primary">
                <span class="material-symbols-outlined text-[18px] text-on-tertiary-container" style="font-variation-settings: 'FILL' 1;">chat</span>
                {{ __('Kontakte sipò sou WhatsApp') }}
            </a>
        @endif

        <p class="relative z-10 mt-space-sm font-label-sm text-label-sm text-outline tracking-wider uppercase">LajanAnsanm · {{ $code }}</p>
    </div>
</x-public-shell>
