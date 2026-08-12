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
            'puntuacion' => 'required|numeric|between:0.5,5',
            'comentario' => 'nullable|string|max:1000',
        ]);
        $validator->after(function ($validator) use ($request) {
            $puntuacion = (float) $request->input('puntuacion');
            if (abs(($puntuacion * 2) - round($puntuacion * 2)) > 0.00001) {
                $validator->errors()->add('puntuacion', 'La puntuación debe avanzar de 0.5 en 0.5.');
            }
        });
        $data = $validator->validate();

        $evaluacion->update([
            'puntuacion' => $data['puntuacion'],
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
                'puntuacion' => $evaluacion->puntuacion,
                'comentario' => $evaluacion->comentario,
                'enviada_at' => $evaluacion->enviada_at?->toIso8601String(),
            ],
        ];
    }
}
