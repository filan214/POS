@props(['title' => '', 'subtitle' => '', 'status' => null])

{{--
    Shared auth shell in the Lapak design — the same split brand/form layout as
    the login screen. Used by the password reset / confirm pages.
--}}
<x-guest-layout :title="$title">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Brand / story panel --}}
        <div class="relative hidden overflow-hidden bg-ink px-12 py-14 text-white lg:flex lg:flex-col">
            <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-jade/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-jade/10 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-jade shadow-sm">
                    <x-icon name="store" class="h-6 w-6" />
                </span>
                <div class="leading-tight">
                    <p class="text-lg font-extrabold tracking-tight">{{ __('common.brand') }}</p>
                    <p class="text-xs text-white/50">{{ __('common.brand_sub') }}</p>
                </div>
            </div>

            <div class="relative mt-auto max-w-md">
                <h2 class="text-4xl font-extrabold leading-tight tracking-tight">{{ __('auth.tagline') }}</h2>
                <ul class="mt-8 space-y-4">
                    @foreach (['feature_pos' => 'pos', 'feature_stock' => 'cube', 'feature_reports' => 'reports'] as $key => $icon)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                <x-icon :name="$icon" class="h-4 w-4 text-jade-100" />
                            </span>
                            <span class="text-sm text-white/75">{{ __('auth.'.$key) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative mt-12 font-mono text-xs text-white/35">© {{ date('Y') }} {{ __('common.brand') }} — Point of Sale</p>
        </div>

        {{-- Form panel --}}
        <div class="flex items-center justify-center px-6 py-12 sm:px-12">
            <div class="w-full max-w-sm">
                {{-- mobile brand --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-jade text-white">
                        <x-icon name="store" class="h-6 w-6" />
                    </span>
                    <span class="text-lg font-extrabold tracking-tight text-ink-900">{{ __('common.brand') }}</span>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-ink-900">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-1.5 text-sm text-ink-500">{{ $subtitle }}</p>
                @endif

                {{-- Success status (e.g. reset link emailed) --}}
                @if ($status)
                    <div class="mt-6 flex items-center gap-2 rounded-xl border border-jade/20 bg-jade-50 px-4 py-3 text-sm font-medium text-jade-700">
                        <x-icon name="check-circle" class="h-5 w-5 shrink-0" />
                        <span>{{ $status }}</span>
                    </div>
                @endif

                {{-- Validation errors --}}
                @if ($errors->any())
                    <div class="mt-6 flex items-center gap-2 rounded-xl border border-chili/20 bg-chili-50 px-4 py-3 text-sm font-medium text-chili-700">
                        <x-icon name="alert" class="h-5 w-5 shrink-0" />
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <div class="mt-8">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-sm text-ink-500">
                    <a href="{{ route('login') }}" class="font-semibold text-jade-700 hover:text-jade-800">&larr; {{ __('auth.back_to_login') }}</a>
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
