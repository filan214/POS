<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('pos'));

// Demo shortcut (guests only) — sign in as a seeded owner/cashier.
Route::get('/login/as/{role}', [LoginController::class, 'loginAs'])
    ->middleware('guest')
    ->name('login.as');

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
