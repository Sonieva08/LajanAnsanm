@php
    $tones = [
        'blue' => 'bg-primary-fixed text-primary-container',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'green' => 'bg-green-100 text-green-700',
        'red' => 'bg-red-100 text-red-700',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
@endphp
<span class="badge {{ $tones[$log->tone()] ?? $tones['slate'] }}">{{ $log->label() }}</span>
