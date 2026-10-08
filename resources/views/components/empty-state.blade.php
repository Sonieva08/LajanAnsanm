@props(['art' => null, 'icon' => 'inbox', 'title', 'text' => null, 'href' => null, 'action' => null, 'chip' => null, 'compact' => false])
@if ($compact)
    <div data-empty-state class="bg-surface-container-lowest rounded-xl p-space-md shadow-sm flex flex-col gap-space-sm text-left">
        <div class="flex items-start gap-space-sm">
            <div class="w-10 h-10 rounded-full bg-surface-container text-primary flex items-center justify-center shrink-0 mt-0.5">
                <span class="material-symbols-outlined text-[22px]">{{ $icon }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="font-title-md text-title-md text-on-surface">{{ $title }}</h4>
                @if ($text)<p class="font-body-sm text-body-sm text-on-surface-variant leading-tight mt-0.5">{{ $text }}</p>@endif
            </div>
        </div>
        @if ($href && $action)
            <div class="flex justify-end">
                <a href="{{ $href }}" class="min-h-[44px] px-space-md py-1.5 rounded-lg bg-surface-container text-primary font-label-md text-label-md flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">restart_alt</span> {{ $action }}
                </a>
            </div>
        @endif
    </div>
@else
    <div data-empty-state class="relative overflow-hidden bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col items-center text-center">
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 bg-surface-container-highest/60 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative w-20 h-20 rounded-full bg-surface-container flex items-center justify-center mb-space-md shadow-sm">
            @switch($art)
                @case('tontine')
                    <svg class="w-10 h-10" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect fill="#0C2382" fill-opacity="0.12" height="24" rx="5" width="36" x="6" y="14"/>
                        <path d="M6 21C6 17.134 9.134 14 13 14H35C38.866 14 42 17.134 42 21V33C42 36.866 38.866 40 35 40H13C9.134 40 6 36.866 6 33V21Z" stroke="#0C2382" stroke-width="2.5"/>
                        <circle cx="24" cy="27" r="5" stroke="#0C2382" stroke-width="2.5"/>
                        <path d="M24 10V14M16 10L18 14M32 10L30 14M24 25V29" stroke="#0C2382" stroke-linecap="round" stroke-width="2.5"/>
                    </svg>
                    <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-surface-container-highest text-primary flex items-center justify-center shadow-sm"><span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'FILL' 1;">add</span></span>
                    @break
                @case('members')
                    <svg class="w-9 h-9" fill="none" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="16" cy="14" r="6" stroke="#0C2382" stroke-width="2.2"/>
                        <path d="M6 31C6 26.029 10.477 22 16 22C21.523 22 26 26.029 26 31" stroke="#0C2382" stroke-linecap="round" stroke-width="2.2"/>
                        <circle cx="28" cy="13" r="4.5" stroke="#0C2382" stroke-dasharray="2 2" stroke-width="2"/>
                        <path d="M27 21C30.314 21.5 33 24.5 33 28.5M31 10V6M29 8H33" stroke="#0C2382" stroke-linecap="round" stroke-width="2"/>
                    </svg>
                    @break
                @case('reports')
                    <svg class="w-9 h-9" fill="none" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 32H32" stroke="#0C2382" stroke-linecap="round" stroke-width="2.2"/>
                        <rect height="8" rx="1.5" stroke="#0C2382" stroke-width="2" width="4" x="11" y="20"/>
                        <rect height="14" rx="1.5" stroke="#0C2382" stroke-width="2" width="4" x="18" y="14"/>
                        <rect height="20" rx="1.5" stroke="#0C2382" stroke-dasharray="2 2" stroke-width="2" width="4" x="25" y="8"/>
                    </svg>
                    @break
                @case('search')
                    <svg class="w-9 h-9" fill="none" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="18" cy="18" r="9" stroke="#0C2382" stroke-width="2.2"/>
                        <path d="M25 25L32 32" stroke="#0C2382" stroke-linecap="round" stroke-width="2.4"/>
                        <path d="M15 15L21 21M21 15L15 21" stroke="#0C2382" stroke-linecap="round" stroke-width="1.8"/>
                    </svg>
                    @break
                @default
                    <span class="material-symbols-outlined text-[34px] text-primary-container">{{ $icon }}</span>
            @endswitch
        </div>

        @if ($chip)
            <div class="relative inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-label-sm text-label-sm mb-space-xs">{{ $chip }}</div>
        @endif

        <h3 class="relative font-headline-md text-headline-md text-on-surface mb-space-xs">{{ $title }}</h3>
        @if ($text)<p class="relative font-body-md text-body-md text-on-surface-variant max-w-xs mb-space-md leading-relaxed">{{ $text }}</p>@endif

        @if ($href && $action)
            <a href="{{ $href }}" class="relative btn-primary w-full">{{ $action }}</a>
        @endif
        {{ $slot }}
    </div>
@endif
