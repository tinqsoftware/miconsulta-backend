<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\EvaluacionAtencion;
use App\Models\Notificacion;
use Illuminate\Support\Str;

class AtencionCompletadaService
{
    public function completar(Cita $cita): EvaluacionAtencion
    {
        $cita->loadMissing(['paciente.usuario', 'profesional']);
        $cita->update(['estado' => 'completada']);

        $evaluacion = $this->obtenerOCrearEvaluacion($cita);

        $profesional = trim(($cita->profesional->nombres ?? '') . ' ' . ($cita->profesional->apellidos ?? ''));
        $titulo = '¿Cómo fue tu atención?';
        $mensaje = 'Cuéntanos cómo fue tu atención con ' . ($profesional ?: 'tu profesional de salud') . '.';
        $ruta = '/evaluacion/' . $cita->id . '?token=' . $evaluacion->token_acceso;

        Notificacion::firstOrCreate(
            [
                'id_paciente' => $cita->id_paciente,
                'tipo' => 'evaluacion_atencion',
                'datos_extra->cita_id' => $cita->id,
            ],
            [
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'categoria' => 'general',
                'datos_extra' => [
                    'cita_id' => $cita->id,
                    'token' => $evaluacion->token_acceso,
                    'ruta' => $ruta,
                ],
                'fue_leida' => false,
                'fecha_envio' => now(),
            ]
        );

        $tokenFcm = $cita->paciente?->usuario?->token_fcm;
        if ($tokenFcm) {
            app(FirebaseMessagingService::class)->send($tokenFcm, $titulo, $mensaje, [
                'ruta' => $ruta,
                'cita_id' => $cita->id,
                'token' => $evaluacion->token_acceso,
            ]);
        }

        return $evaluacion;
    }

    public function obtenerOCrearEvaluacion(Cita $cita): EvaluacionAtencion
    {
        return EvaluacionAtencion::firstOrCreate(
            ['id_cita' => $cita->id],
            [
                'id_paciente' => $cita->id_paciente,
                'id_profesional' => $cita->id_profesional,
                'token_acceso' => Str::random(64),
            ]
        );
    }

    public function enlace(EvaluacionAtencion $evaluacion): string
    {
        return route('evaluacion.enlace', [
            'cita' => $evaluacion->id_cita,
            'token' => $evaluacion->token_acceso,
        ]);
    }
}
