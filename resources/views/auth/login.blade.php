<x-guest-layout :title="__('auth.welcome')">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Brand / story panel --}}
        <div class="relative hidden overflow-hidden bg-ink px-12 py-14 text-white lg:flex lg:flex-col">
            {{-- ambient glow --}}
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

                <h1 class="text-2xl font-bold tracking-tight text-ink-900">{{ __('auth.welcome') }}</h1>
                <p class="mt-1.5 text-sm text-ink-500">{{ __('auth.subtitle') }}</p>

                <form method="POST" action="{{ route('login.attempt') }}" class="mt-8 space-y-4">
                    @csrf
                    <div>
                        <label class="label" for="email">{{ __('auth.email') }}</label>
                        <input id="email" name="email" type="email" value="owner@warung.id"
                               placeholder="{{ __('auth.email_placeholder') }}" class="input" autocomplete="username">
                    </div>
                    <div>
                        <label class="label" for="password">{{ __('auth.password') }}</label>
                        <input id="password" name="password" type="password" value="password"
                               class="input" autocomplete="current-password">
                    </div>
                    <label class="flex items-center gap-2.5 text-sm text-ink-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-ink/25 text-jade focus:ring-jade">
                        {{ __('auth.remember') }}
                    </label>
                    <button type="submit" class="btn-primary w-full">{{ __('auth.sign_in') }}</button>
                </form>

                {{-- Demo accounts --}}
                <div class="mt-8">
                    <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-wide text-ink-400">
                        <span class="h-px flex-1 bg-ink/10"></span>
                        {{ __('auth.demo_title') }}
                        <span class="h-px flex-1 bg-ink/10"></span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <a href="{{ route('login.as', 'owner') }}" class="btn-outline">{{ __('auth.as_owner') }}</a>
                        <a href="{{ route('login.as', 'cashier') }}" class="btn-outline">{{ __('auth.as_cashier') }}</a>
                    </div>
                    <p class="mt-3 text-center text-xs text-ink-400">{{ __('auth.demo_hint') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
