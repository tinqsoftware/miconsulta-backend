<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\EvaluacionAtencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluacionAtencionController extends Controller
{
    public function show(Request $request, Cita $cita)
    {
        $evaluacion = $this->evaluacionAutorizada($request, $cita);

        return response()->json([
            'data' => $this->payload($cita, $evaluacion),
        ]);
    }

    public function store(Request $request, Cita $cita)
    {
        $evaluacion = $this->evaluacionAutorizada($request, $cita);

        if ($evaluacion->enviada_at) {
            return response()->json(['message' => 'Esta atención ya fue calificada.'], 409);
        }

        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'conexion_puntualidad' => 'required|integer|between:1,5',
            'escucha_trato' => 'required|integer|between:1,5',
            'explicacion_diagnostico' => 'required|integer|between:1,5',
            'explicacion_tratamiento' => 'required|integer|between:1,5',
            'claridad_proximos_pasos' => 'required|integer|between:1,5',
            'comentario' => 'nullable|string|max:1000',
        ]);
        $data = $validator->validate();

        $evaluacion->update([
            // Conservamos la columna histórica como resumen interno. La UI y
            // las reglas de negocio trabajan exclusivamente con enteros 1-5.
            'puntuacion' => null,
            'conexion_puntualidad' => $data['conexion_puntualidad'],
            'escucha_trato' => $data['escucha_trato'],
            'explicacion_diagnostico' => $data['explicacion_diagnostico'],
            'explicacion_tratamiento' => $data['explicacion_tratamiento'],
            'claridad_proximos_pasos' => $data['claridad_proximos_pasos'],
            'comentario' => $data['comentario'] ?? null,
            'enviada_at' => now(),
        ]);

        return response()->json([
            'message' => 'Gracias por calificar tu atención.',
            'data' => $this->payload($cita, $evaluacion->fresh()),
        ]);
    }

    private function evaluacionAutorizada(Request $request, Cita $cita): EvaluacionAtencion
    {
        $paciente = $request->user()->paciente;
        abort_unless($paciente && (int) $cita->id_paciente === (int) $paciente->id, 403);

        $evaluacion = EvaluacionAtencion::where('id_cita', $cita->id)->firstOrFail();
        abort_unless(hash_equals($evaluacion->token_acceso, (string) $request->input('token')), 403);

        return $evaluacion;
    }

    private function payload(Cita $cita, EvaluacionAtencion $evaluacion): array
    {
        $cita->loadMissing(['profesional', 'especialidad', 'ipress']);

        return [
            'cita' => [
                'id' => $cita->id,
                'profesional' => trim(($cita->profesional->nombres ?? '') . ' ' . ($cita->profesional->apellidos ?? '')),
                'especialidad' => $cita->especialidad?->nombre,
                'fecha' => $cita->fecha,
                'hora' => $cita->hora,
                'modalidad' => $cita->tipo_cita,
                'ipress' => $cita->ipress?->nombre,
            ],
            'evaluacion' => [
                'enviada' => (bool) $evaluacion->enviada_at,
                'calificaciones' => [
                    'conexion_puntualidad' => $evaluacion->conexion_puntualidad,
                    'escucha_trato' => $evaluacion->escucha_trato,
                    'explicacion_diagnostico' => $evaluacion->explicacion_diagnostico,
                    'explicacion_tratamiento' => $evaluacion->explicacion_tratamiento,
                    'claridad_proximos_pasos' => $evaluacion->claridad_proximos_pasos,
                ],
                'comentario' => $evaluacion->comentario,
                'enviada_at' => $evaluacion->enviada_at?->toIso8601String(),
            ],
        ];
    }
}
