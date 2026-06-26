@props(['variant' => 'ink', 'dot' => false])

@php
    $classes = [
        'jade' => 'badge-jade',
        'amber' => 'badge-amber',
        'chili' => 'badge-chili',
        'ink' => 'badge-ink',
    ][$variant] ?? 'badge-ink';

    $dotColor = [
        'jade' => 'bg-jade',
        'amber' => 'bg-amber',
        'chili' => 'bg-chili',
        'ink' => 'bg-ink-500',
    ][$variant] ?? 'bg-ink-500';
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }}"></span>
    @endif
    {{ $slot }}
</span>
