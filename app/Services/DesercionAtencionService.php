<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\DesercionAtencion;
use App\Models\Notificacion;
use Illuminate\Support\Str;

class DesercionAtencionService
{
    public function registrar(Cita $cita): DesercionAtencion
    {
        $cita->loadMissing('paciente.usuario');
        $cita->update(['estado' => 'desercion']);

        $desercion = DesercionAtencion::firstOrCreate(
            ['id_cita' => $cita->id],
            [
                'id_paciente' => $cita->id_paciente,
                'token_acceso' => Str::random(64),
            ]
        );

        $ruta = '/desercion/' . $cita->id . '?token=' . $desercion->token_acceso;
        $notificacion = Notificacion::firstOrCreate(
            [
                'id_paciente' => $cita->id_paciente,
                'tipo' => 'desercion_atencion',
                'datos_extra->cita_id' => $cita->id,
            ],
            [
                'titulo' => 'Registra tu motivo de deserción',
                'mensaje' => 'Cuéntanos el motivo por el que no pudiste completar tu atención.',
                'categoria' => 'general',
                'datos_extra' => [
                    'cita_id' => $cita->id,
                    'token' => $desercion->token_acceso,
                    'ruta' => $ruta,
                ],
                'fue_leida' => false,
                'fecha_envio' => now(),
            ]
        );

        if ($notificacion->wasRecentlyCreated) {
            $tokenFcm = $cita->paciente?->usuario?->token_fcm;
            if ($tokenFcm) {
                app(FirebaseMessagingService::class)->send(
                    $tokenFcm,
                    $notificacion->titulo,
                    $notificacion->mensaje,
                    [
                        'ruta' => $ruta,
                        'cita_id' => $cita->id,
                        'token' => $desercion->token_acceso,
                    ]
                );
            }

            app(RobotCallService::class)->desercion($cita);
        }

        return $desercion;
    }
}
