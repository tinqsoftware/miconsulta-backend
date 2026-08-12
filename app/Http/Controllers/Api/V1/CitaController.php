<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Cita;
use App\Models\HorarioDisponible;
use App\Models\Profesional;

class CitaController extends Controller
{
    public function getHorarios(Request $request)
    {
        $request->validate([
            'tipo_cita' => 'required|in:presencial,telemedicina',
            'fecha' => 'nullable|date'
        ]);

        $paciente = $request->user()->paciente;
        if (!$paciente) {
            return response()->json(['data' => []]);
        }

        $zonaHoraria = config('app.timezone');
        $hoy = now($zonaHoraria)->startOfDay();
        $fecha = Carbon::parse($request->fecha ?? $hoy->toDateString(), $zonaHoraria)->startOfDay();

        if ($fecha->lt($hoy)) {
            return response()->json(['message' => 'Solo se pueden consultar horarios desde hoy.'], 422);
        }

        $this->generarAgendaDiaria($fecha, $request->tipo_cita, $paciente->id_ipress_asignada);

        $query = HorarioDisponible::with(['profesional', 'especialidad', 'ipress'])
            ->where('tipo_cita', $request->tipo_cita)
            ->where('esta_disponible', true)
            ->whereRaw('cupo_ocupado < cupo_maximo')
            ->whereDate('fecha', $fecha->toDateString());

        if ($paciente->id_ipress_asignada) {
            $query->where('id_ipress', $paciente->id_ipress_asignada);
        }

        // El paciente solo puede reservar con una hora completa de anticipación.
        if ($fecha->isSameDay($hoy)) {
            $query->where('hora_inicio', '>=', now($zonaHoraria)->addHour()->format('H:i:s'));
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

            $inicio = Carbon::parse(
                $horario->fecha . ' ' . $horario->hora_inicio,
                config('app.timezone')
            );
            if ($inicio->lt(now(config('app.timezone'))->addHour())) {
                DB::rollBack();
                return response()->json([
                    'message' => 'La cita debe solicitarse con al menos una hora de anticipación.',
                ], 422);
            }

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

        $hoy = now(config('app.timezone'))->toDateString();
        
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

    private function generarAgendaDiaria(Carbon $fecha, string $tipoCita, ?int $idIpress): void
    {
        $profesionales = Profesional::query()
            ->where('esta_activo', true)
            ->whereNotNull('id_especialidad')
            ->whereNotNull('id_ipress');

        if ($idIpress) {
            $profesionales->where('id_ipress', $idIpress);
        }

        $profesionales->get()->each(function (Profesional $profesional) use ($fecha, $tipoCita) {
            $inicio = $fecha->copy()->setTime(7, 0);
            $finJornada = $fecha->copy()->setTime(20, 0);

            while ($inicio->lt($finJornada)) {
                HorarioDisponible::firstOrCreate(
                    [
                        'id_profesional' => $profesional->id,
                        'fecha' => $fecha->toDateString(),
                        'hora_inicio' => $inicio->format('H:i:s'),
                        'tipo_cita' => $tipoCita,
                    ],
                    [
                        'id_especialidad' => $profesional->id_especialidad,
                        'id_ipress' => $profesional->id_ipress,
                        'hora_fin' => $inicio->copy()->addMinutes(15)->format('H:i:s'),
                        'cupo_maximo' => 1,
                        'cupo_ocupado' => 0,
                        'esta_disponible' => true,
                    ]
                );

                $inicio->addMinutes(15);
            }
        });
    }
}
