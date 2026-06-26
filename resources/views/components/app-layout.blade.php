@props(['title' => '', 'active' => ''])

@php
    $user = auth()->user();
    $isOwner = $user->isOwner();
    $shift = $user->openShift();
    $lowStockCount = \App\Models\Product::active()->lowStock()->count();

    $nav = [
        ['key' => 'pos', 'route' => 'pos', 'icon' => 'pos', 'label' => __('common.nav.pos'), 'show' => true],
        ['key' => 'shifts', 'route' => 'shifts', 'icon' => 'shifts', 'label' => __('common.nav.shifts'), 'show' => true],
        ['key' => 'products', 'route' => 'products', 'icon' => 'products', 'label' => __('common.nav.products'), 'show' => $isOwner],
        ['key' => 'reports', 'route' => 'reports', 'icon' => 'reports', 'label' => __('common.nav.reports'), 'show' => $isOwner],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ __('common.brand') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&family=jetbrains-mono:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-paper text-ink-800">
<div class="min-h-screen lg:flex" x-data="{ sidebar: false }">

    {{-- Mobile overlay --}}
    <div x-show="sidebar" x-transition.opacity @click="sidebar = false"
         class="fixed inset-0 z-40 bg-ink-900/50 lg:hidden" style="display:none"></div>

    {{-- Sidebar --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-ink text-white transition-transform duration-200 ease-out lg:static lg:z-auto lg:translate-x-0">
        {{-- Brand --}}
        <div class="flex items-center gap-3 px-5 py-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-jade text-white shadow-sm">
                <x-icon name="store" class="h-6 w-6" />
            </span>
            <div class="leading-tight">
                <p class="font-extrabold tracking-tight">{{ __('common.brand') }}</p>
                <p class="text-xs text-white/45">{{ __('common.brand_sub') }}</p>
            </div>
            <button @click="sidebar = false" class="ml-auto rounded-lg p-1.5 text-white/60 hover:bg-white/10 lg:hidden">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-2 scroll-slim">
            <div class="space-y-1">
                <p class="px-3.5 pb-1 text-[10px] font-bold uppercase tracking-widest text-white/30">{{ __('common.nav.section_main') }}</p>
                @foreach (collect($nav)->where('key', '!=', 'products')->where('key', '!=', 'reports') as $item)
                    <x-nav-link :href="route($item['route'])" :icon="$item['icon']" :active="$active === $item['key']">{{ $item['label'] }}</x-nav-link>
                @endforeach
            </div>

            @if ($isOwner)
                <div class="space-y-1">
                    <p class="px-3.5 pb-1 text-[10px] font-bold uppercase tracking-widest text-white/30">{{ __('common.nav.section_manage') }}</p>
                    <x-nav-link :href="route('products')" icon="products" :active="$active === 'products'">{{ __('common.nav.products') }}</x-nav-link>
                    <x-nav-link :href="route('reports')" icon="reports" :active="$active === 'reports'">{{ __('common.nav.reports') }}</x-nav-link>
                </div>
            @endif
        </nav>

        {{-- Shift status + user --}}
        <div class="space-y-3 border-t border-white/10 p-3">
            @if ($shift)
                <a href="{{ route('shifts') }}" class="flex items-center gap-2.5 rounded-xl bg-white/[.04] px-3.5 py-2.5 hover:bg-white/[.07]">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-pulse-dot rounded-full bg-jade"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-jade"></span>
                    </span>
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-xs font-semibold text-white">{{ __('common.misc.shift_open') }} · {{ $shift->code }}</p>
                        <p class="text-[11px] text-white/45">{{ $shift->opened_at->format('H:i') }} · {{ $shift->cashier->name }}</p>
                    </div>
                </a>
            @else
                <a href="{{ route('shifts') }}" class="flex items-center gap-2.5 rounded-xl bg-white/[.04] px-3.5 py-2.5 hover:bg-white/[.07]">
                    <span class="h-2.5 w-2.5 rounded-full bg-white/30"></span>
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-xs font-semibold text-white/80">{{ __('shifts.none_title') }}</p>
                        <p class="text-[11px] text-white/45">{{ __('shifts.open_action') }}</p>
                    </div>
                </a>
            @endif

            <div class="flex items-center gap-3 px-1.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-jade/20 text-sm font-bold text-jade-100">{{ $user->initials }}</span>
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                    <p class="text-xs text-white/45">{{ __('common.role.'.$user->role) }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg p-2 text-white/55 hover:bg-white/10 hover:text-white" title="{{ __('common.action.logout') }}">
                        <x-icon name="logout" class="h-5 w-5" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-ink/[.06] bg-paper/85 px-4 py-3 backdrop-blur sm:px-6">
            <button @click="sidebar = true" class="rounded-lg p-2 text-ink-700 hover:bg-ink/5 lg:hidden">
                <x-icon name="menu" class="h-6 w-6" />
            </button>

            <div class="flex items-center gap-2 lg:hidden">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-jade text-white">
                    <x-icon name="store" class="h-4 w-4" />
                </span>
                <span class="font-extrabold tracking-tight text-ink-900">{{ __('common.brand') }}</span>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <x-locale-switcher tone="dark" />
                <button class="relative rounded-xl border border-ink/10 bg-white p-2 text-ink-600 hover:text-ink-900">
                    <x-icon name="bell" class="h-5 w-5" />
                    @if ($lowStockCount > 0)
                        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-chili px-1 text-[10px] font-bold text-white">{{ $lowStockCount }}</span>
                    @endif
                </button>
                <span class="hidden h-9 w-9 items-center justify-center rounded-full bg-ink text-sm font-bold text-white sm:flex">{{ $user->initials }}</span>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
