<x-auth-card :title="__('auth.reset_title')" :subtitle="__('auth.reset_subtitle')">
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label class="label" for="email">{{ __('auth.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
                   class="input" autocomplete="username" autofocus required>
        </div>
        <div>
            <label class="label" for="password">{{ __('auth.new_password') }}</label>
            <input id="password" name="password" type="password" class="input" autocomplete="new-password" required>
        </div>
        <div>
            <label class="label" for="password_confirmation">{{ __('auth.confirm_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn-primary w-full">
            <x-icon name="check" class="h-5 w-5" /> {{ __('auth.reset_action') }}
        </button>
    </form>
</x-auth-card>
