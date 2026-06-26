<x-auth-card :title="__('auth.confirm_title')" :subtitle="__('auth.confirm_subtitle')">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="password">{{ __('auth.password_label') }}</label>
            <input id="password" name="password" type="password" class="input" autocomplete="current-password" autofocus required>
        </div>
        <button type="submit" class="btn-primary w-full">
            <x-icon name="check" class="h-5 w-5" /> {{ __('auth.confirm_action') }}
        </button>
    </form>
</x-auth-card>
