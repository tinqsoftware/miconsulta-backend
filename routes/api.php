<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CitaController;
use App\Http\Controllers\Api\V1\RecetaController;
use App\Http\Controllers\Api\V1\TomasController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Controllers\Api\V1\PerfilController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\API\RecordatorioController;

Route::prefix('v1')->group(function () {
    // Auth (Públicas)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/olvide-contrasena', [AuthController::class, 'olvideContrasena']);
    Route::post('/auth/verificar-codigo', [AuthController::class, 'verificarCodigo']);
    Route::post('/auth/cambiar-contrasena', [AuthController::class, 'cambiarContrasena']);

    // Banners (Pública o protegida, la pondremos pública para facilidad)
    Route::get('/banners/activos', [BannerController::class, 'activos']);

    // Rutas protegidas (Requieren token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/biometria', [AuthController::class, 'actualizarBiometria']);
        
        // El usuario logueado
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Citas
        Route::get('/citas/horarios', [CitaController::class, 'getHorarios']);
        Route::post('/citas', [CitaController::class, 'crearCita']);
        Route::get('/citas/mis-citas', [CitaController::class, 'misCitas']);

        // Recetas
        Route::get('/recetas/mis-recetas', [RecetaController::class, 'misRecetas']);
        Route::get('/recetas/{id}/pdf', [RecetaController::class, 'descargarPdf']);

        // Seguimiento de Tomas (Antiguos)
        Route::get('/tomas/mis-tomas', [TomasController::class, 'misTomas']);
        Route::put('/tomas/{id}/estado', [TomasController::class, 'marcarToma']);
        Route::get('/tomas/adherencia', [TomasController::class, 'getAdherencia']);

        // Nuevas Rutas Recordatorios y Seguimiento
        Route::put('/recetas/{id}/toggle-recordatorios', [RecordatorioController::class, 'toggleRecordatorios']);
        Route::post('/recetas/{id}/programar-tomas', [RecordatorioController::class, 'programarTomas']);
        Route::get('/recetas/{id}/tomas-dia', [RecordatorioController::class, 'obtenerTomasPorDia']);
        Route::put('/tomas/{id_toma}', [RecordatorioController::class, 'marcarToma']);

        // Notificaciones
        Route::get('/notificaciones', [NotificacionController::class, 'misNotificaciones']);
        Route::put('/notificaciones/{id}/leida', [NotificacionController::class, 'marcarLeida']);
        Route::get('/notificaciones/no-leidas', [NotificacionController::class, 'noLeidasCount']);

        // Perfil
        Route::get('/perfil', [PerfilController::class, 'miPerfil']);
    });
});
