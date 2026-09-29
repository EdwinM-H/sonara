<?php

use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\VoiceLoginController;
use App\Http\Controllers\VoiceRegistrationController;
use App\Http\Controllers\CustomerRequestController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Portal público
// ---------------------------------------------------------------------------
Route::get('/', [PublicPortalController::class, 'home'])->name('public.home');
Route::get('/explorar', [PublicPortalController::class, 'explore'])->name('public.explore');
Route::get('/categorias', [PublicPortalController::class, 'categories'])->name('public.categories');
Route::get('/categorias/{category:slug}', [PublicPortalController::class, 'category'])->name('public.category');
Route::get('/emprendimientos/{business:slug}', [PublicPortalController::class, 'business'])->name('public.business');
Route::get('/publicaciones/{publication:slug}', [PublicPortalController::class, 'publication'])->name('public.publication');

// ---------------------------------------------------------------------------
// Solicitudes de clientes (requiere autenticación como cliente)
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'active', 'role:customer'])->group(function () {
    Route::post('/solicitudes', [CustomerRequestController::class, 'store'])->name('customer.requests.store');
    Route::get('/mis-solicitudes', [CustomerRequestController::class, 'index'])->name('customer.requests.index');
    Route::get('/mis-solicitudes/{request}', [CustomerRequestController::class, 'show'])->name('customer.requests.show');
    Route::patch('/mis-solicitudes/{request}/cancelar', [CustomerRequestController::class, 'cancel'])->name('customer.requests.cancel');
});

// ---------------------------------------------------------------------------
// Registro de emprendedor por voz
// ---------------------------------------------------------------------------
Route::get('/registro/voz', [VoiceRegistrationController::class, 'index'])->name('voice-registration.index');
Route::get('/registro/voz/iniciar', [VoiceRegistrationController::class, 'start'])->name('voice-registration.start');
Route::get('/registro/voz/retomar', [VoiceRegistrationController::class, 'resume'])->name('voice-registration.resume');
Route::post('/registro/voz/respuesta', [VoiceRegistrationController::class, 'process'])->name('voice-registration.process');

// ---------------------------------------------------------------------------
// Login de emprendedor por voz (nombre completo + PIN de 4 dígitos)
// ---------------------------------------------------------------------------
Route::get('/login/voz', [VoiceLoginController::class, 'index'])->name('voice-login.index');
Route::get('/login/voz/iniciar', [VoiceLoginController::class, 'start'])->name('voice-login.start');
Route::post('/login/voz/respuesta', [VoiceLoginController::class, 'process'])->name('voice-login.process');