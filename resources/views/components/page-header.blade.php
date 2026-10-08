@props(['back' => null, 'title', 'subtitle' => null])
<div class="flex items-center gap-3 mb-space-lg">
    @if ($back)
        <a href="{{ $back }}" class="w-11 h-11 shrink-0 flex items-center justify-center rounded-full bg-surface-container-lowest shadow-sm active:scale-95 transition" aria-label="{{ __('Retounen') }}">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
    @endif
    <div class="min-w-0 flex-1">
        <h1 class="font-headline-lg-mobile text-headline-lg-mobile truncate">{{ $title }}</h1>
        @if ($subtitle)<p class="font-body-sm text-body-sm text-on-surface-variant truncate">{{ $subtitle }}</p>@endif
    </div>
    {{ $slot }}
</div>
