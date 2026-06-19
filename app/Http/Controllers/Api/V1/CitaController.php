<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Cita;
use App\Models\HorarioDisponible;

class CitaController extends Controller
{
    public function getHorarios(Request $request)
    {
        $request->validate([
            'tipo_cita' => 'required|in:presencial,telemedicina',
            'fecha' => 'nullable|date'
        ]);

        $query = HorarioDisponible::with(['profesional', 'especialidad', 'ipress'])
            ->where('tipo_cita', $request->tipo_cita)
            ->where('esta_disponible', true)
            ->whereRaw('cupo_ocupado < cupo_maximo')
            ->whereDate('fecha', '>=', now()->toDateString());

        if ($request->fecha) {
            $query->whereDate('fecha', $request->fecha);
        }

        $horarios = $query->orderBy('fecha')->orderBy('hora_inicio')->get();

        return response()->json(['data' => $horarios]);
    }

    public function crearCita(Request $request)
    {
        $request->validate([
            'id_horario' => 'required|exists:horarios_disponibles,id',
            'motivo_consulta' => 'nullable|string',
            'url_audio_sintomas' => 'nullable|string', // Aquí enviaremos el texto convertido en MVP
        ]);

        $usuario = $request->user();
        $paciente = $usuario->paciente;

        if (!$paciente) {
            return response()->json(['message' => 'El usuario no tiene un paciente asociado'], 400);
        }

        try {
            DB::beginTransaction();

            $horario = HorarioDisponible::lockForUpdate()->findOrFail($request->id_horario);

            if ($horario->cupo_ocupado >= $horario->cupo_maximo || !$horario->esta_disponible) {
                DB::rollBack();
                return response()->json(['message' => 'El horario seleccionado ya no tiene cupos disponibles'], 400);
            }

            // Crear la cita
            $cita = Cita::create([
                'id_paciente' => $paciente->id,
                'id_horario' => $horario->id,
                'id_profesional' => $horario->id_profesional,
                'id_especialidad' => $horario->id_especialidad,
                'id_ipress' => $horario->id_ipress,
                'tipo_cita' => $horario->tipo_cita,
                'fecha' => $horario->fecha,
                'hora' => $horario->hora_inicio,
                'estado' => 'confirmada', // Automáticamente confirmada según MVP
                'motivo_consulta' => $request->motivo_consulta,
                'url_audio_sintomas' => $request->url_audio_sintomas, // Texto del dictado
                'id_usuario' => $usuario->id
            ]);

            // Actualizar cupo
            $horario->increment('cupo_ocupado');
            if ($horario->cupo_ocupado >= $horario->cupo_maximo) {
                $horario->update(['esta_disponible' => false]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Cita reservada exitosamente',
                'data' => $cita->load(['profesional', 'especialidad', 'ipress'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al reservar la cita', 'error' => $e->getMessage()], 500);
        }
    }

    public function misCitas(Request $request)
    {
        $paciente = $request->user()->paciente;

        if (!$paciente) {
            return response()->json(['data' => []]);
        }

        $citas = Cita::with(['profesional', 'especialidad', 'ipress'])
            ->where('id_paciente', $paciente->id)
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();

        $hoy = now()->toDateString();
        
        $proximas = $citas->filter(function ($cita) use ($hoy) {
            return $cita->fecha >= $hoy && in_array($cita->estado, ['programada', 'confirmada']);
        })->values();

        $pendientes_o_pasadas = $citas->filter(function ($cita) use ($hoy) {
            return $cita->fecha < $hoy || in_array($cita->estado, ['pendiente_programacion', 'completada', 'cancelada', 'no_asistio']);
        })->values();

        return response()->json([
            'proximas' => $proximas,
            'pendientes_y_pasadas' => $pendientes_o_pasadas
        ]);
    }
}
