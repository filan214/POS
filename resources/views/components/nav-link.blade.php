@props(['href' => '#', 'icon' => null, 'active' => false])

<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'nav-link '.($active ? 'nav-link-active' : '')]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
    @endif
    <span>{{ $slot }}</span>
</a>
