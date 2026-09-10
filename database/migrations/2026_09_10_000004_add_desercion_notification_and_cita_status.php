<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE citas MODIFY estado ENUM('programada','confirmada','completada','cancelada','no_asistio','pendiente_programacion','desercion') DEFAULT 'programada'");
        DB::statement("ALTER TABLE notificaciones MODIFY tipo ENUM('toma_programada','toma_registrada','toma_pospuesta','toma_olvidada','cita_confirmada','resultados_disponibles','recordatorio_cita','aviso_importante','evaluacion_atencion','desercion_atencion','validacion_celular') NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('citas')->where('estado', 'desercion')->update(['estado' => 'no_asistio']);
        DB::statement("ALTER TABLE citas MODIFY estado ENUM('programada','confirmada','completada','cancelada','no_asistio','pendiente_programacion') DEFAULT 'programada'");
        DB::statement("ALTER TABLE notificaciones MODIFY tipo ENUM('toma_programada','toma_registrada','toma_pospuesta','toma_olvidada','cita_confirmada','resultados_disponibles','recordatorio_cita','aviso_importante','evaluacion_atencion') NOT NULL");
    }
};
