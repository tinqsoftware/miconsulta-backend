<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\ConfiguracionController;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Rutas del Panel Web (Sin sesión)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/reset-db', [AdminController::class, 'resetDb'])->name('reset.db');

    // Pacientes
    Route::get('/pacientes', [AdminController::class, 'pacientes'])->name('pacientes');
    
    // Citas
    Route::get('/citas', [AdminController::class, 'citas'])->name('citas');
    Route::post('/citas/{id}/estado', [AdminController::class, 'cambiarEstadoCita'])->name('citas.estado');

    // Recetas
    Route::get('/recetas', [AdminController::class, 'recetas'])->name('recetas');
    Route::get('/recetas/crear', [AdminController::class, 'createReceta'])->name('recetas.create');
    Route::post('/recetas/crear', [AdminController::class, 'storeReceta'])->name('recetas.store');

    // Banners
    Route::get('/banners', [AdminController::class, 'banners'])->name('banners');
    Route::get('/banners/crear', [AdminController::class, 'createBanner'])->name('banners.create');
    Route::post('/banners/crear', [AdminController::class, 'storeBanner'])->name('banners.store');
    Route::get('/banners/{id}/editar', [AdminController::class, 'editBanner'])->name('banners.edit');
    Route::post('/admin/banners/{id}', [BannerController::class, 'update'])->name('admin.banners.update');
    Route::delete('/admin/banners/{id}', [BannerController::class, 'destroy'])->name('admin.banners.destroy');

    // Configuraciones
    Route::get('/admin/configuraciones', [ConfiguracionController::class, 'index'])->name('admin.configuraciones');
    Route::post('/admin/configuraciones', [ConfiguracionController::class, 'update'])->name('admin.configuraciones.update');
});
