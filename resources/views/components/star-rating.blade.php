@props(['value' => 0, 'size' => 'text-[20px]'])
@php $full = max(0, min(5, (int) round((float) $value))); @endphp
<span role="img" aria-label="{{ __(':n sou 5', ['n' => $full]) }}" {{ $attributes->class(['inline-flex items-center text-amber-400']) }}>
    @for ($i = 1; $i <= 5; $i++)
        <span class="material-symbols-outlined {{ $size }} {{ $i <= $full ? '' : 'text-slate-300' }}" style="font-variation-settings:'FILL' {{ $i <= $full ? 1 : 0 }}" aria-hidden="true">star</span>
    @endfor
</span>
