<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend-only routes
|--------------------------------------------------------------------------
| These render the Blade UI against in-memory mock data (App\Support\MockData).
| Auth, persistence and role middleware land in the backend phase; for now the
| demo login buttons just set a `role` in the session to drive the UI.
*/

Route::get('/', fn () => redirect()->route('pos'));

// ---- Auth (mock) ----------------------------------------------------------
Route::get('/login', fn () => view('auth.login'))->name('login');

Route::post('/login', function () {
    session(['role' => 'owner']);

    return redirect()->route('pos');
})->name('login.attempt');

Route::get('/login/as/{role}', function (string $role) {
    abort_unless(in_array($role, ['owner', 'cashier'], true), 404);
    session(['role' => $role]);

    return redirect()->route($role === 'owner' ? 'reports' : 'pos');
})->name('login.as');

Route::get('/logout', function () {
    session()->forget('role');

    return redirect()->route('login');
})->name('logout');

// ---- Locale switch --------------------------------------------------------
Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'id'], true), 404);
    session(['locale' => $locale]);

    return redirect()->back(fallback: route('pos'));
})->name('locale.switch');

// ---- App screens ----------------------------------------------------------
Route::get('/pos', fn () => view('pos.index'))->name('pos');
Route::get('/products', fn () => view('products.index'))->name('products');
Route::get('/shifts', fn () => view('shifts.index'))->name('shifts');
Route::get('/reports', fn () => view('reports.dashboard'))->name('reports');
