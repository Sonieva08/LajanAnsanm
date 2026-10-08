@props(['name' => '', 'rank' => null, 'tone' => 'bg-primary-fixed text-primary-container'])
@php
    $i = collect(preg_split('/\s+/', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
@endphp
<span class="relative inline-flex w-11 h-11 shrink-0">
    <span class="w-11 h-11 rounded-full flex items-center justify-center font-label-md text-label-md {{ $tone }}">{{ $i }}</span>
    @if ($rank)
        <span class="absolute -bottom-0.5 -right-0.5 w-[18px] h-[18px] rounded-full bg-primary-container text-on-primary text-[10px] font-bold flex items-center justify-center ring-2 ring-white">{{ $rank }}</span>
    @endif
</span>
