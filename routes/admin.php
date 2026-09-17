<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\EntrepreneurManagementController;
use App\Http\Controllers\BusinessViewController;
use App\Http\Controllers\PublicationReviewController;
use App\Http\Controllers\CategoryManagementController;
use App\Http\Controllers\AssistanceController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AssistedRegistrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/publicaciones/{publication}/cambiar-estado', [PublicationReviewController::class, 'changeStatus'])->name('publications.changeStatus');

    // Usuarios
    Route::get('/usuarios', [AdminController::class, 'users'])->name('users');
    Route::get('/usuarios/{user}/editar', [AdminController::class, 'editUser'])->name('users.edit');
    Route::patch('/usuarios/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::patch('/usuarios/{user}/suspender', [AdminController::class, 'suspend'])->name('users.suspend');
    Route::patch('/usuarios/{user}/reactivar', [AdminController::class, 'reactivate'])->name('users.reactivate');

    // Emprendedores
    Route::get('/emprendedores', [EntrepreneurManagementController::class, 'index'])->name('entrepreneurs.index');
    Route::get('/emprendedores/crear', [EntrepreneurManagementController::class, 'create'])->name('entrepreneurs.create');
    Route::post('/emprendedores', [EntrepreneurManagementController::class, 'store'])->name('entrepreneurs.store');
    Route::get('/emprendedores/{user}/ver', [EntrepreneurManagementController::class, 'show'])->name('entrepreneurs.show');
    Route::get('/emprendedores/{user}/editar', [EntrepreneurManagementController::class, 'edit'])->name('entrepreneurs.edit');
    Route::patch('/emprendedores/{user}', [EntrepreneurManagementController::class, 'update'])->name('entrepreneurs.update');
    Route::post('/emprendedores/{user}/aprobar', [EntrepreneurManagementController::class, 'approve'])->name('entrepreneurs.approve');
    Route::post('/emprendedores/{user}/rechazar', [EntrepreneurManagementController::class, 'reject'])->name('entrepreneurs.reject');
    Route::post('/emprendedores/{user}/correccion', [EntrepreneurManagementController::class, 'requestCorrection'])->name('entrepreneurs.correction');
    Route::get('/emprendedores/documentos/{document}', [DocumentController::class, 'show'])->name('entrepreneurs.documents.show');

    // Registro asistido
    Route::get('/registro-asistido', [AssistedRegistrationController::class, 'index'])->name('assisted.index');
    Route::post('/registro-asistido', [AssistedRegistrationController::class, 'store'])->name('assisted.store');

    // Emprendimientos
    Route::get('/emprendimientos', [BusinessViewController::class, 'index'])->name('businesses.index');
    Route::get('/emprendimientos/{business}/ver', [BusinessViewController::class, 'show'])->name('businesses.show');
    Route::patch('/emprendimientos/{business}/estado', [BusinessViewController::class, 'toggleStatus'])->name('businesses.toggleStatus');

    // Publicaciones (revisión)
    Route::get('/publicaciones', [PublicationReviewController::class, 'index'])->name('publications.index');
    Route::get('/publicaciones/{publication}/ver', [PublicationReviewController::class, 'show'])->name('publications.show');

    // Categorías
    Route::get('/categorias', [CategoryManagementController::class, 'index'])->name('categories.index');
    Route::get('/categorias/crear', [CategoryManagementController::class, 'create'])->name('categories.create');
    Route::post('/categorias', [CategoryManagementController::class, 'store'])->name('categories.store');
    Route::get('/categorias/{category}/editar', [CategoryManagementController::class, 'edit'])->name('categories.edit');
    Route::patch('/categorias/{category}', [CategoryManagementController::class, 'update'])->name('categories.update');
    Route::delete('/categorias/{category}', [CategoryManagementController::class, 'destroy'])->name('categories.destroy');

    // Subcategorías
    Route::get('/subcategorias', [CategoryManagementController::class, 'subcategories'])->name('subcategories.index');
    Route::get('/subcategorias/crear', [CategoryManagementController::class, 'createSubcategory'])->name('subcategories.create');
    Route::post('/subcategorias', [CategoryManagementController::class, 'storeSubcategory'])->name('subcategories.store');
    Route::get('/subcategorias/{subcategory}/editar', [CategoryManagementController::class, 'editSubcategory'])->name('subcategories.edit');
    Route::patch('/subcategorias/{subcategory}', [CategoryManagementController::class, 'updateSubcategory'])->name('subcategories.update');
    Route::delete('/subcategorias/{subcategory}', [CategoryManagementController::class, 'destroySubcategory'])->name('subcategories.destroy');

    // Solicitudes
    Route::get('/solicitudes', [AdminController::class, 'requests'])->name('requests');

    // Asistencia
    Route::get('/asistencia', [AssistanceController::class, 'adminIndex'])->name('assistance.index');
    Route::get('/asistencia/{assistance}', [AssistanceController::class, 'adminShow'])->name('assistance.show');
    Route::patch('/asistencia/{assistance}', [AssistanceController::class, 'adminUpdate'])->name('assistance.update');

    // Notificaciones (admin)
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notificaciones/{notification}/leida', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Auditoría
    Route::get('/auditoria', [AuditController::class, 'index'])->name('audit.index');

    // Configuración
    Route::get('/configuracion', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/configuracion', [SettingsController::class, 'update'])->name('settings.update');
});