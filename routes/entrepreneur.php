<?php

use App\Http\Controllers\EntrepreneurController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\AIFlyerController;
use App\Http\Controllers\EntrepreneurRequestController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AssistanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AccessibilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:entrepreneur'])->prefix('emprendedor')->name('entrepreneur.')->group(function () {

    Route::get('/dashboard', [EntrepreneurController::class, 'dashboard'])->name('dashboard');
    Route::get('/perfil', [EntrepreneurController::class, 'profile'])->name('profile');
    Route::patch('/perfil', [EntrepreneurController::class, 'updateProfile'])->name('profile.update');

    // Accesibilidad
    Route::get('/accesibilidad', [AccessibilityController::class, 'entrepreneurIndex'])->name('accessibility');
    Route::patch('/accesibilidad', [AccessibilityController::class, 'update'])->name('accessibility.update');

    // Emprendimientos
    Route::get('/emprendimientos', [BusinessController::class, 'index'])->name('businesses.index');
    Route::get('/emprendimientos/crear', [BusinessController::class, 'create'])->name('businesses.create');
    Route::post('/emprendimientos', [BusinessController::class, 'store'])->name('businesses.store');
    Route::get('/emprendimientos/{business}/editar', [BusinessController::class, 'edit'])->name('businesses.edit');
    Route::patch('/emprendimientos/{business}', [BusinessController::class, 'update'])->name('businesses.update');
    Route::delete('/emprendimientos/{business}', [BusinessController::class, 'destroy'])->name('businesses.destroy');

    // Publicaciones
    Route::get('/publicaciones', [PublicationController::class, 'index'])->name('publications.index');
    Route::get('/publicaciones/crear', [PublicationController::class, 'create'])->name('publications.create');
    Route::post('/publicaciones', [PublicationController::class, 'store'])->name('publications.store');
    Route::get('/publicaciones/{publication}/editar', [PublicationController::class, 'edit'])->name('publications.edit');
    Route::patch('/publicaciones/{publication}', [PublicationController::class, 'update'])->name('publications.update');
    Route::post('/publicaciones/{publication}/solicitar-aprobacion', [PublicationController::class, 'requestApproval'])->name('publications.requestApproval');
    Route::delete('/publicaciones/{publication}', [PublicationController::class, 'destroy'])->name('publications.destroy');

    // Flyers IA
    Route::get('/publicaciones/{publication}/flyer', [AIFlyerController::class, 'index'])->name('flyers.index');
    Route::post('/publicaciones/{publication}/flyer/generar', [AIFlyerController::class, 'generate'])->name('flyers.generate');
    Route::post('/publicaciones/{publication}/flyer/{generation}/aprobar', [AIFlyerController::class, 'approve'])->name('flyers.approve');
    Route::get('/mis-imagenes', [AIFlyerController::class, 'history'])->name('flyers.history');

    // Solicitudes recibidas
    Route::get('/solicitudes', [EntrepreneurRequestController::class, 'index'])->name('requests.index');
    Route::get('/solicitudes/{request}', [EntrepreneurRequestController::class, 'show'])->name('requests.show');
    Route::patch('/solicitudes/{request}/aceptar', [EntrepreneurRequestController::class, 'accept'])->name('requests.accept');
    Route::patch('/solicitudes/{request}/rechazar', [EntrepreneurRequestController::class, 'reject'])->name('requests.reject');
    Route::patch('/solicitudes/{request}/completar', [EntrepreneurRequestController::class, 'complete'])->name('requests.complete');

    // Documentación
    Route::get('/documentacion', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documentacion', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documentacion/{document}/descargar', [DocumentController::class, 'show'])->name('documents.show');

    // Asistencia
    Route::get('/asistencia', [AssistanceController::class, 'index'])->name('assistance.index');
    Route::post('/asistencia', [AssistanceController::class, 'store'])->name('assistance.store');

    // Notificaciones
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notificaciones/{notification}/leida', [NotificationController::class, 'markRead'])->name('notifications.read');
});