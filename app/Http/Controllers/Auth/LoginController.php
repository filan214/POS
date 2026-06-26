<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Demo shortcut — sign in as the seeded owner or cashier without a
     * password. Login/logout proper are handled by Breeze's
     * AuthenticatedSessionController (see routes/auth.php).
     */
    public function loginAs(Request $request, string $role): RedirectResponse
    {
        abort_unless(in_array($role, ['owner', 'cashier'], true), 404);

        $user = User::role($role)->orderBy('id')->firstOrFail();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($role === 'owner' ? 'reports' : 'pos');
    }
}
