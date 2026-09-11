<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\DesercionAtencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DesercionAtencionController extends Controller
{
    private const MOTIVOS = [
        'empeoramiento_emergencia' => 'Empeoramiento de molestias y atención por emergencia',
        'conflicto_laboral_academico' => 'Conflicto laboral o académico',
        'imprevisto_familiar_personal' => 'Imprevisto familiar o personal',
        'confusion_horario' => 'Confusión con el horario',
        'problemas_conexion' => 'Problemas de conexión a internet',
        'dificultad_aplicacion' => 'Dificultad para usar la app',
        'falla_dispositivo' => 'Falla del dispositivo',
    ];

    public function show(Request $request, Cita $cita)
    {
        $desercion = $this->autorizada($request, $cita);

        return response()->json(['data' => $this->payload($cita, $desercion)]);
    }

    public function store(Request $request, Cita $cita)
    {
        $desercion = $this->autorizada($request, $cita);

        if ($desercion->enviada_at) {
            return response()->json(['message' => 'El motivo de deserción ya fue registrado.'], 409);
        }

        $data = Validator::make($request->all(), [
            'token' => 'required|string',
            'motivo' => 'required|string|in:' . implode(',', array_keys(self::MOTIVOS)),
        ])->validate();

        $desercion->update([
            'motivo_codigo' => $data['motivo'],
            'motivo_descripcion' => self::MOTIVOS[$data['motivo']],
            'enviada_at' => now(),
        ]);

        return response()->json([
            'message' => 'Gracias por registrar el motivo de deserción.',
            'data' => $this->payload($cita, $desercion->fresh()),
        ]);
    }

    private function autorizada(Request $request, Cita $cita): DesercionAtencion
    {
        $paciente = $request->user()->paciente;
        abort_unless($paciente && (int) $cita->id_paciente === (int) $paciente->id, 403);

        $desercion = DesercionAtencion::where('id_cita', $cita->id)->firstOrFail();
        abort_unless(hash_equals($desercion->token_acceso, (string) $request->input('token')), 403);

        return $desercion;
    }

    private function payload(Cita $cita, DesercionAtencion $desercion): array
    {
        $cita->loadMissing(['profesional', 'especialidad']);

        return [
            'cita' => [
                'id' => $cita->id,
                'profesional' => trim(($cita->profesional?->nombres ?? '') . ' ' . ($cita->profesional?->apellidos ?? '')),
                'especialidad' => $cita->especialidad?->nombre,
                'fecha' => $cita->fecha,
                'hora' => $cita->hora,
                'modalidad' => $cita->tipo_cita,
            ],
            'desercion' => [
                'enviada' => (bool) $desercion->enviada_at,
                'motivo' => $desercion->motivo_codigo,
                'motivo_descripcion' => $desercion->motivo_descripcion,
                'enviada_at' => $desercion->enviada_at?->toIso8601String(),
            ],
            'motivos' => collect(self::MOTIVOS)->map(
                fn (string $descripcion, string $codigo) => ['codigo' => $codigo, 'descripcion' => $descripcion]
            )->values(),
        ];
    }
}
