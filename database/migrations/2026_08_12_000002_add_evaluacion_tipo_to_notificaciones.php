<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notificaciones MODIFY tipo ENUM('toma_programada','toma_registrada','toma_pospuesta','toma_olvidada','cita_confirmada','resultados_disponibles','recordatorio_cita','aviso_importante','evaluacion_atencion') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notificaciones MODIFY tipo ENUM('toma_programada','toma_registrada','toma_pospuesta','toma_olvidada','cita_confirmada','resultados_disponibles','recordatorio_cita','aviso_importante') NOT NULL");
        }
    }
};
