<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', fn () => redirect()->route('pos'));

// Deploy hook — runs pending migrations on a host with no SSH (see DEPLOY.md).
// Disabled (404) unless DEPLOY_MIGRATE_TOKEN is set on the server; protected by
// a constant-time token check. Session + CSRF middleware are stripped so it
// works on the very first deploy, before the `sessions` table exists (both the
// session store and the CSRF cookie would otherwise touch the missing table).
Route::get('/deploy/migrate', [DeployController::class, 'migrate'])
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class])
    ->name('deploy.migrate');

// Demo shortcut — passwordless sign-in as a seeded owner/cashier.
// Registered ONLY in local/testing so it can never bypass auth in production.
if (app()->environment(['local', 'testing'])) {
    Route::get('/login/as/{role}', [LoginController::class, 'loginAs'])
        ->middleware('guest')
        ->name('login.as');
}

// Locale switch — available to guests (login page) too.
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// ---- Authenticated app ----------------------------------------------------
Route::middleware('auth')->group(function () {
    // POS / sales — both roles.
    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    Route::post('/pos/sale', [PosController::class, 'store'])->name('pos.sale');

    // Shifts — both roles (own shift); history scoped in controller.
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts');
    Route::post('/shifts/open', [ShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shifts/close', [ShiftController::class, 'close'])->name('shifts.close');

    // Management — owner only.
    Route::middleware('role:owner')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/{product}', [ProductController::class, 'update'])->name('products.update');

        Route::get('/reports', [ReportController::class, 'dashboard'])->name('reports');
        Route::get('/reports/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    });
});

// Breeze auth backend (login, logout, password reset/confirm).
require __DIR__.'/auth.php';
