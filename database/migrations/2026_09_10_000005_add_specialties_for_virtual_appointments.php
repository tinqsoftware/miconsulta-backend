<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['Medicina General', 'MED_GEN'],
            ['Medicina Familiar', 'MED_FAM'],
            ['Psicología', 'PSICO'],
            ['Nutrición', 'NUTRI'],
        ] as [$nombre, $codigo]) {
            DB::table('especialidades')->updateOrInsert(
                ['nombre' => $nombre],
                ['codigo' => $codigo, 'esta_activa' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // No se eliminan especialidades para conservar las referencias históricas.
    }
};
