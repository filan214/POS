<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['en', 'id'], true), 404);

        $request->session()->put('locale', $locale);

        // Persist the choice on the account so it sticks across sessions.
        if ($user = $request->user()) {
            $user->update(['locale' => $locale]);
        }

        return redirect()->back(fallback: route('pos'));
    }
}
