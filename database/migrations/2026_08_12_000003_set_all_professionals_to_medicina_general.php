<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $idMedicinaGeneral = DB::table('especialidades')
            ->where('nombre', 'Medicina General')
            ->value('id');

        if (!$idMedicinaGeneral) {
            return;
        }

        DB::table('profesionales')->update(['id_especialidad' => $idMedicinaGeneral]);
        DB::table('horarios_disponibles')
            ->where('cupo_ocupado', 0)
            ->where('esta_disponible', true)
            ->update(['id_especialidad' => $idMedicinaGeneral]);
    }

    public function down(): void
    {
        // Los valores previos no pueden reconstruirse de manera segura.
    }
};
