@props(['method', 'size' => 'h-10 w-10'])
@php
    $logos = [
        'moncash' => ['file' => 'images/moncash_1.png', 'name' => 'MonCash'],
        'natcash' => ['file' => 'images/natcash_1.jpg', 'name' => 'NatCash'],
    ];
    $logo = $logos[$method] ?? null;
@endphp
@if ($logo)
    <img src="{{ asset($logo['file']) }}" alt="{{ $logo['name'] }}" {{ $attributes->class([$size, 'shrink-0 rounded-lg object-cover']) }}>
@endif
