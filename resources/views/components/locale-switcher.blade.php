@props(['tone' => 'light']) {{-- 'light' on dark bg, 'dark' on light bg --}}

@php
    $current = app()->getLocale();
    if ($tone === 'dark') {
        $wrap = 'border-ink/10 bg-ink/[.03]';
        $idle = 'text-ink-500 hover:text-ink-800';
        $on = 'bg-white text-ink-900 shadow-sm';
    } else {
        $wrap = 'border-white/10 bg-white/[.06]';
        $idle = 'text-white/55 hover:text-white';
        $on = 'bg-white/15 text-white';
    }
@endphp

<div {{ $attributes->merge(['class' => "inline-flex items-center gap-0.5 rounded-xl border p-0.5 $wrap"]) }}>
    @foreach (['id' => 'ID', 'en' => 'EN'] as $code => $abbr)
        <a href="{{ route('locale.switch', $code) }}"
           class="rounded-lg px-2.5 py-1 text-xs font-bold tracking-wide transition-colors {{ $current === $code ? $on : $idle }}">
            {{ $abbr }}
        </a>
    @endforeach
</div>
