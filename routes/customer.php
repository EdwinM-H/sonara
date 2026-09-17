<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AccessibilityController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Autenticación y perfil común (Breeze)
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::patch('/accesibilidad', [AccessibilityController::class, 'update'])->name('accessibility.update');
});

// ---------------------------------------------------------------------------
// Panel cliente
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'active', 'role:customer'])->prefix('cliente')->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
});