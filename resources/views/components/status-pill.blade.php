@props(['tone' => 'gray', 'pulse' => false])

@php
$tones = [
    'green' => 'bg-green-500/10 text-green-400 border-green-500/30',
    'blue' => 'bg-accent-blue/10 text-accent-blue border-accent-blue/30',
    'gold' => 'bg-accent-gold/10 text-accent-gold border-accent-gold/30',
    'red' => 'bg-accent-red/10 text-accent-red border-accent-red/30',
    'gray' => 'bg-bg-tertiary text-text-secondary border-border',
];
$classes = $tones[$tone] ?? $tones['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs border flex-shrink-0 {$classes}".($pulse ? ' animate-pulse' : '')]) }}>{{ $slot }}</span>
