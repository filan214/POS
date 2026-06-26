<x-auth-card :title="__('auth.forgot_title')" :subtitle="__('auth.forgot_subtitle')" :status="session('status')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="email">{{ __('auth.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   placeholder="{{ __('auth.email_placeholder') }}" class="input" autocomplete="username" autofocus required>
        </div>
        <button type="submit" class="btn-primary w-full">
            <x-icon name="bell" class="h-5 w-5" /> {{ __('auth.send_reset_link') }}
        </button>
    </form>
</x-auth-card>
