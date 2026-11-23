@props(['label' => null])
{{-- Petite aide en ligne : une icône "?" qui déplie une phrase. Fonctionne là où Alpine est chargé. --}}
<span x-data="{ open: false }" class="inline-flex flex-col align-top" data-hint>
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="w-5 h-5 rounded-full bg-secondary-container text-primary-container inline-flex items-center justify-center hover:opacity-80"
            aria-label="{{ $label ?? __('Plis enfòmasyon') }}">
        <span class="material-symbols-outlined text-[13px]">help</span>
    </button>
    <span x-show="open" x-cloak class="block mt-1 rounded-lg bg-secondary-container/40 p-2 font-body-sm text-body-sm text-on-secondary-fixed-variant leading-relaxed">{{ $slot }}</span>
</span>
