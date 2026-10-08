@props(['labels', 'series', 'height' => 220])
@php
    $w = 600; $h = $height; $pl = 44; $pr = 10; $pt = 10; $pb = 26;
    $max = max(1, (float) collect($series)->flatMap(fn ($s) => $s['values'])->max());
    $count = max(1, count($labels));
    $slot = ($w - $pl - $pr) / $count;
    $k = count($series);
    $bw = max(2, min(18, ($slot * 0.8) / max(1, $k)));
    $y = fn ($v) => $pt + ($h - $pt - $pb) * (1 - $v / $max);
    $short = fn ($v) => $v >= 1000000 ? round($v / 1000000, 1) . 'M' : ($v >= 1000 ? round($v / 1000, 1) . 'k' : (string) round($v));
    $every = max(1, (int) ceil($count / 7));
@endphp
<svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-auto" role="img">
    @for ($g = 0; $g <= 4; $g++)
        @php $gy = $pt + ($h - $pt - $pb) * $g / 4; @endphp
        <line x1="{{ $pl }}" x2="{{ $w - $pr }}" y1="{{ $gy }}" y2="{{ $gy }}" stroke="#e2e8f0" stroke-width="1" />
        <text x="{{ $pl - 6 }}" y="{{ $gy + 3 }}" text-anchor="end" font-size="10" fill="#64748b">{{ $short($max * (1 - $g / 4)) }}</text>
    @endfor
    @foreach ($labels as $i => $label)
        @php $cx = $pl + $slot * $i + $slot / 2; @endphp
        @foreach ($series as $j => $s)
            @php $v = (float) $s['values'][$i]; $bx = $cx - ($k * $bw) / 2 + $j * $bw; @endphp
            <rect x="{{ round($bx, 1) }}" y="{{ round($y($v), 1) }}" width="{{ $bw - 1 }}" height="{{ round(($h - $pb) - $y($v), 1) }}" rx="2" fill="{{ $s['color'] }}" />
        @endforeach
        @if ($i % $every === 0)
            <text x="{{ $cx }}" y="{{ $h - 8 }}" text-anchor="middle" font-size="10" fill="#64748b">{{ $label }}</text>
        @endif
    @endforeach
</svg>
