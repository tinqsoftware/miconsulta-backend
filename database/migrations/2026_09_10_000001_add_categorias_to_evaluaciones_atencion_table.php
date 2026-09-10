<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones_atencion', function (Blueprint $table) {
            $table->unsignedTinyInteger('conexion_puntualidad')->nullable()->after('puntuacion');
            $table->unsignedTinyInteger('escucha_trato')->nullable()->after('conexion_puntualidad');
            $table->unsignedTinyInteger('explicacion_diagnostico')->nullable()->after('escucha_trato');
            $table->unsignedTinyInteger('explicacion_tratamiento')->nullable()->after('explicacion_diagnostico');
            $table->unsignedTinyInteger('claridad_proximos_pasos')->nullable()->after('explicacion_tratamiento');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones_atencion', function (Blueprint $table) {
            $table->dropColumn([
                'conexion_puntualidad',
                'escucha_trato',
                'explicacion_diagnostico',
                'explicacion_tratamiento',
                'claridad_proximos_pasos',
            ]);
        });
    }
};
