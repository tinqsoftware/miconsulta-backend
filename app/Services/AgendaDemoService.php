<?php

namespace App\Services;

use App\Models\HorarioDisponible;
use App\Models\Profesional;

class AgendaDemoService
{
    public function asegurar(
        string $tipoCita,
        ?int $idIpress = null,
        ?int $idEspecialidad = null,
        bool $force = false
    ): int {
        if (!$force && !config('citas.modo_prueba') && !app()->environment(['local', 'testing'])) {
            return 0;
        }

        $profesionales = Profesional::query()
            ->where('esta_activo', true)
            ->whereNotNull('id_especialidad')
            ->whereNotNull('id_ipress')
            ->when($idIpress, fn ($query) => $query->where('id_ipress', $idIpress))
            ->when($idEspecialidad, fn ($query) => $query->where('id_especialidad', $idEspecialidad))
            ->get();

        $creados = 0;
        foreach ($profesionales as $profesional) {
            if (!$this->tieneAgendaFutura($profesional->id, $tipoCita)) {
                $creados += $this->crearParaProfesional($profesional, $tipoCita);
            }
        }

        return $creados;
    }

    private function tieneAgendaFutura(int $idProfesional, string $tipoCita): bool
    {
        return HorarioDisponible::query()
            ->where('id_profesional', $idProfesional)
            ->where('tipo_cita', $tipoCita)
            ->where('esta_disponible', true)
            ->whereRaw('cupo_ocupado < cupo_maximo')
            ->whereDate('fecha', '>=', now(config('app.timezone'))->toDateString())
            ->exists();
    }

    private function crearParaProfesional(Profesional $profesional, string $tipoCita): int
    {
        $inicio = now(config('app.timezone'))->startOfDay();
        $dias = max(1, (int) config('citas.dias_agenda_demo', 14));
        $creados = 0;

        for ($dia = 0; $dia < $dias; $dia++) {
            $fecha = $inicio->copy()->addDays($dia);
            $hora = $fecha->copy()->setTime((int) config('citas.hora_inicio_demo', 7), 0);
            $fin = $fecha->copy()->setTime((int) config('citas.hora_fin_demo', 20), 0);
            while ($hora->lt($fin)) {
                $horario = HorarioDisponible::firstOrCreate(
                    [
                        'id_profesional' => $profesional->id,
                        'fecha' => $fecha->toDateString(),
                        'hora_inicio' => $hora->format('H:i:s'),
                        'tipo_cita' => $tipoCita,
                    ],
                    [
                        'id_especialidad' => $profesional->id_especialidad,
                        'id_ipress' => $profesional->id_ipress,
                        'hora_fin' => $hora->copy()->addMinutes((int) config('citas.duracion_slot_demo', 15))->format('H:i:s'),
                        'cupo_maximo' => 1,
                        'cupo_ocupado' => 0,
                        'esta_disponible' => true,
                    ]
                );
                $creados += $horario->wasRecentlyCreated ? 1 : 0;
                $hora->addMinutes((int) config('citas.duracion_slot_demo', 15));
            }
        }

        return $creados;
    }
}
