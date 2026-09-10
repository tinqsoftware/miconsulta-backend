<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConfiguracionController;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Enlace HTTPS para compartir por WhatsApp/SMS. Al tocarlo, conserva el
// esquema que ya entiende la app instalada y abre la evaluación indicada.
Route::get('/evaluacion/{cita}', function (int $cita) {
    $token = request()->query('token');

    abort_unless(is_string($token) && $token !== '', 404);

    return redirect()->away(
        'miconsulta://evaluacion/' . $cita . '?token=' . rawurlencode($token)
    );
})->whereNumber('cita')->name('evaluacion.enlace');

// El mismo patrón permite que un enlace HTTPS de deserción abra el formulario
// correspondiente en la aplicación instalada.
Route::get('/desercion/{cita}', function (int $cita) {
    $token = request()->query('token');

    abort_unless(is_string($token) && $token !== '', 404);

    return redirect()->away(
        'miconsulta://desercion/' . $cita . '?token=' . rawurlencode($token)
    );
})->whereNumber('cita')->name('desercion.enlace');

// Rutas del Panel Web (Sin sesión)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/reset-db', [AdminController::class, 'resetDb'])->name('reset.db');

    // Pacientes
    Route::get('/pacientes', [AdminController::class, 'pacientes'])->name('pacientes');
    
    // Citas
    Route::get('/citas', [AdminController::class, 'citas'])->name('citas');
    Route::post('/citas/{id}/estado', [AdminController::class, 'cambiarEstadoCita'])->name('citas.estado');
    Route::post('/citas/{id}/desercion', [AdminController::class, 'registrarDesercion'])->name('citas.desercion');

    // Recetas
    Route::get('/recetas', [AdminController::class, 'recetas'])->name('recetas');
    Route::get('/recetas/crear', [AdminController::class, 'createReceta'])->name('recetas.create');
    Route::post('/recetas/crear', [AdminController::class, 'storeReceta'])->name('recetas.store');

    // Banners
    Route::get('/banners', [AdminController::class, 'banners'])->name('banners');
    Route::get('/banners/crear', [AdminController::class, 'createBanner'])->name('banners.create');
    Route::post('/banners/crear', [AdminController::class, 'storeBanner'])->name('banners.store');
    Route::get('/banners/{id}/editar', [AdminController::class, 'editBanner'])->name('banners.edit');
    Route::post('/banners/{id}/estado', [AdminController::class, 'toggleBannerEstado'])->name('banners.estado');
    Route::post('/banners/{id}', [AdminController::class, 'updateBanner'])->name('banners.update');
    Route::delete('/banners/{id}', [AdminController::class, 'destroyBanner'])->name('banners.destroy');

    // Configuraciones
    Route::get('/configuraciones', [ConfiguracionController::class, 'index'])->name('configuraciones');
    Route::post('/configuraciones', [ConfiguracionController::class, 'update'])->name('configuraciones.update');
});
