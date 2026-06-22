@props([
    'label' => '',
    'value' => '',
    'delta' => null,        // numeric percentage, e.g. 12.4 or -3.1
    'deltaLabel' => null,   // text under the delta
    'icon' => null,
    'tone' => 'ink',        // accent for the icon chip
])

@php
    $chip = [
        'jade' => 'bg-jade-50 text-jade-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'chili' => 'bg-chili-50 text-chili-700',
        'ink' => 'bg-ink/[.06] text-ink-700',
    ][$tone] ?? 'bg-ink/[.06] text-ink-700';

    $up = $delta !== null && $delta >= 0;
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-ink-500">{{ $label }}</p>
        @if ($icon)
            <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $chip }}">
                <x-icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>
    <p class="mt-3 font-mono text-2xl font-bold tracking-tight text-ink-900 tabular">{{ $value }}</p>
    @if ($delta !== null)
        <div class="mt-2 flex items-center gap-1.5 text-xs">
            <span class="inline-flex items-center gap-0.5 font-semibold {{ $up ? 'text-jade-700' : 'text-chili-700' }}">
                <x-icon :name="$up ? 'arrow-up' : 'arrow-down'" class="h-3.5 w-3.5" />
                {{ abs($delta) }}%
            </span>
            @if ($deltaLabel)
                <span class="text-ink-500">{{ $deltaLabel }}</span>
            @endif
        </div>
    @endif
</div>
