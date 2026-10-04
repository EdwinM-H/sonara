<?php

use App\Http\Controllers\CatalogApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->get('/dashboard', function () {
    $user = auth()->user();

    if ($user->isEntrepreneur()) {
        return redirect()->route('entrepreneur.dashboard');
    }

    if ($user->isCustomer()) {
        return redirect()->route('customer.dashboard');
    }

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('login');
})->name('dashboard');

// API de catálogos (solo lectura) para el panel del emprendedor y el admin.
Route::middleware(['auth', 'active', 'role:entrepreneur,admin'])->prefix('api')->name('api.')->group(function () {
    Route::get('/catalogos', [CatalogApiController::class, 'index'])->name('catalogs.index');
    Route::get('/catalogos/{slug}', [CatalogApiController::class, 'show'])->name('catalogs.show');
});

require __DIR__.'/public.php';
require __DIR__.'/customer.php';
require __DIR__.'/entrepreneur.php';
require __DIR__.'/admin.php';
require __DIR__.'/auth.php';