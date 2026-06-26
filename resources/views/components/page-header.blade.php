@props(['title' => '', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
