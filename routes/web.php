<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;

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
});
