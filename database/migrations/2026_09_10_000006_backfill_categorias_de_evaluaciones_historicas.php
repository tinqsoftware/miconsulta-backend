<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('evaluaciones_atencion')
            ->whereNotNull('puntuacion')
            ->whereNull('conexion_puntualidad')
            ->orderBy('id')
            ->chunkById(100, function ($evaluaciones): void {
                foreach ($evaluaciones as $evaluacion) {
                    $calificacion = max(1, min(5, (int) round((float) $evaluacion->puntuacion)));

                    DB::table('evaluaciones_atencion')
                        ->where('id', $evaluacion->id)
                        ->update([
                            'conexion_puntualidad' => $calificacion,
                            'escucha_trato' => $calificacion,
                            'explicacion_diagnostico' => $calificacion,
                            'explicacion_tratamiento' => $calificacion,
                            'claridad_proximos_pasos' => $calificacion,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // La migración conserva el dato histórico; no debe borrar respuestas ya registradas.
    }
};
