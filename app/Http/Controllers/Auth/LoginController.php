<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('pos') : view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => __('auth.failed')])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->home());
    }

    /**
     * Demo shortcut — sign in as the seeded owner or cashier without a password.
     */
    public function loginAs(string $role): RedirectResponse
    {
        abort_unless(in_array($role, ['owner', 'cashier'], true), 404);

        $user = User::where('role', $role)->orderBy('id')->firstOrFail();
        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route($role === 'owner' ? 'reports' : 'pos');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function home(): string
    {
        return Auth::user()?->isOwner() ? route('reports') : route('pos');
    }
}
