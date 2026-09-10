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
        'no_pude_ingresar' => 'No pude ingresar a la videollamada.',
        'problemas_conexion' => 'Tuve problemas de conexión a internet.',
        'profesional_no_ingreso' => 'El profesional no ingresó a la videollamada.',
        'espera_prolongada' => 'El tiempo de espera fue muy prolongado.',
        'horario_incompatible' => 'No pude atenderme en el horario programado.',
        'problema_dispositivo' => 'Tuve problemas con mi celular, cámara o micrófono.',
        'atencion_otro_centro' => 'Recibí atención en otro establecimiento de salud.',
        'me_senti_indispuesto' => 'Me sentí indispuesto(a) y no pude continuar.',
        'privacidad_ambiente' => 'No contaba con un ambiente privado o adecuado.',
        'otro_motivo' => 'Otro motivo personal.',
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
