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
        // preference → app default ('id').
        $locale = $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? config('app.locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
