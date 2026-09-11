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
use App\Http\Controllers\Api\RecordatorioController;
use App\Http\Controllers\Api\V1\ConfiguracionApiController;
use App\Http\Controllers\Api\V1\EvaluacionAtencionController;
use App\Http\Controllers\Api\V1\DesercionAtencionController;
use App\Http\Controllers\Api\V1\ValidacionCelularController;
use App\Http\Controllers\Api\V1\IpressController;

Route::prefix('v1')->group(function () {
    // Auth (Públicas)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/olvide-contrasena', [AuthController::class, 'olvideContrasena']);
    Route::post('/auth/verificar-codigo', [AuthController::class, 'verificarCodigo']);
    Route::post('/auth/cambiar-contrasena', [AuthController::class, 'cambiarContrasena']);

    // Banners (Pública o protegida, la pondremos pública para facilidad)
    Route::get('/banners/activos', [BannerController::class, 'activos']);
    Route::get('/configuraciones', [ConfiguracionApiController::class, 'index']);

    // Rutas protegidas (Requieren token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/biometria', [AuthController::class, 'actualizarBiometria']);
        Route::put('/auth/token-fcm', [AuthController::class, 'actualizarTokenFcm']);
        Route::delete('/auth/token-fcm', [AuthController::class, 'eliminarTokenFcm']);
        
        // El usuario logueado
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Citas
        Route::get('/especialidades/disponibles', [CitaController::class, 'especialidadesDisponibles']);
        Route::get('/citas/horarios', [CitaController::class, 'getHorarios']);
        Route::post('/citas', [CitaController::class, 'crearCita']);
        Route::get('/citas/mis-citas', [CitaController::class, 'misCitas']);
        Route::get('/citas/atenciones-realizadas', [CitaController::class, 'atencionesRealizadas']);

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

        // Evaluación de la atención médica
        Route::get('/evaluaciones/citas/{cita}', [EvaluacionAtencionController::class, 'show']);
        Route::post('/evaluaciones/citas/{cita}', [EvaluacionAtencionController::class, 'store']);
        Route::get('/deserciones/citas/{cita}', [DesercionAtencionController::class, 'show']);
        Route::post('/deserciones/citas/{cita}', [DesercionAtencionController::class, 'store']);

        // Perfil
        Route::get('/perfil', [PerfilController::class, 'miPerfil']);
        Route::get('/ipress/activas', [IpressController::class, 'activas']);
        Route::post('/perfil/celular/solicitar-validacion', [ValidacionCelularController::class, 'solicitar']);
        Route::post('/perfil/celular/confirmar-validacion', [ValidacionCelularController::class, 'confirmar']);
    });
});
