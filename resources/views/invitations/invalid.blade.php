<x-guest-layout>
    <div class="flex flex-col gap-space-md text-center">
        <span class="material-symbols-outlined text-[44px] text-outline mx-auto">link_off</span>
        <h1 class="font-headline-md text-headline-md text-primary">{{ __('Envitasyon sa a pa valab ankò') }}</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">{{ __('Mande òganizatè a pou l voye w yon nouvo lyen.') }}</p>
        <a href="{{ route('home') }}" class="btn-secondary w-full">{{ __('Tounen sou Akèy') }}</a>
    </div>
</x-guest-layout>
