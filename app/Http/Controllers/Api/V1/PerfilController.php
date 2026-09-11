<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ValidacionCelular;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function miPerfil(Request $request)
    {
        $usuario = $request->user();
        $paciente = $usuario->paciente()->with('ipress')->first();
        $validacion = ValidacionCelular::where('id_usuario', $usuario->id)->first();

        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        return response()->json([
            'usuario' => [
                'dni' => $usuario->dni,
                'correo' => $usuario->correo,
                'usa_biometria' => (bool)$usuario->usa_biometria,
            ],
            'paciente' => [
                'nombres' => $paciente->nombres,
                'apellidos' => $paciente->apellido_paterno . ' ' . $paciente->apellido_materno,
                'fecha_nacimiento' => $paciente->fecha_nacimiento,
                'tipo_seguro' => $paciente->tipo_seguro,
                'celular' => $paciente->celular,
                'celular_validado' => (bool) $validacion?->verificado_at,
                'direccion' => $paciente->direccion . ', ' . $paciente->distrito,
            ],
            'ipress' => $paciente->ipress ? [
                'nombre' => $paciente->ipress->nombre,
                'direccion' => $paciente->ipress->direccion,
                'latitud' => $paciente->ipress->latitud,
                'longitud' => $paciente->ipress->longitud,
                'telefono' => $paciente->ipress->telefono,
                'horario' => $paciente->ipress->horario_atencion,
                'nivel' => $paciente->ipress->nivel,
                'tiene_emergencia' => (bool) $paciente->ipress->tiene_emergencia,
                'servicios' => $paciente->ipress->servicios ?? [],
            ] : null
        ]);
    }
}
