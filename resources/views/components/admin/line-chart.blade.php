@props(['labels', 'series', 'height' => 220])
@php
    $w = 600; $h = $height; $pl = 40; $pr = 10; $pt = 10; $pb = 26;
    $max = max(1, (float) collect($series)->flatMap(fn ($s) => $s['values'])->max());
    $n = max(1, count($labels) - 1);
    $x = fn ($i) => $pl + ($w - $pl - $pr) * ($i / $n);
    $y = fn ($v) => $pt + ($h - $pt - $pb) * (1 - $v / $max);
    $short = fn ($v) => $v >= 1000000 ? round($v / 1000000, 1) . 'M' : ($v >= 1000 ? round($v / 1000, 1) . 'k' : (string) round($v));
    $every = max(1, (int) ceil(count($labels) / 7));
@endphp
<svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-auto" role="img">
    @for ($g = 0; $g <= 4; $g++)
        @php $gy = $pt + ($h - $pt - $pb) * $g / 4; @endphp
        <line x1="{{ $pl }}" x2="{{ $w - $pr }}" y1="{{ $gy }}" y2="{{ $gy }}" stroke="#e2e8f0" stroke-width="1" />
        <text x="{{ $pl - 6 }}" y="{{ $gy + 3 }}" text-anchor="end" font-size="10" fill="#64748b">{{ $short($max * (1 - $g / 4)) }}</text>
    @endfor
    @foreach ($labels as $i => $label)
        @if ($i % $every === 0)
            <text x="{{ $x($i) }}" y="{{ $h - 8 }}" text-anchor="middle" font-size="10" fill="#64748b">{{ $label }}</text>
        @endif
    @endforeach
    @foreach ($series as $s)
        @php $pts = collect($s['values'])->map(fn ($v, $i) => round($x($i), 1) . ',' . round($y($v), 1))->implode(' '); @endphp
        <polyline points="{{ $pts }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
        @foreach ($s['values'] as $i => $v)
            @if (count($labels) <= 31)<circle cx="{{ $x($i) }}" cy="{{ $y($v) }}" r="2.5" fill="{{ $s['color'] }}" />@endif
        @endforeach
    @endforeach
</svg>
