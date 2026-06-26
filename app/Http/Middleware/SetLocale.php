<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the active locale for the request.
     *
     * Resolution order (frontend phase): session value → app default ('id').
     * Once auth lands this also reads the persisted `users.locale` column.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['en', 'id'];

        // Resolution order: explicit session choice → signed-in user's saved
        // preference → app default ('id'). Guard on hasSession() so the
        // middleware is safe on session-less routes (e.g. the deploy hook),
        // where both session() and the session-based user() would otherwise
        // throw. On normal web routes the session is always present by now.
        $locale = config('app.locale');

        if ($request->hasSession()) {
            $locale = $request->session()->get('locale')
                ?? $request->user()?->locale
                ?? $locale;
        }

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
