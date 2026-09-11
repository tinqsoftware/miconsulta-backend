<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipress', function (Blueprint $table): void {
            $table->boolean('tiene_emergencia')->default(false)->after('esta_activa');
            $table->json('servicios')->nullable()->after('tiene_emergencia');
        });

        $serviciosPorNivel = [
            'I' => ['Medicina general', 'Farmacia'],
            'II' => ['Medicina general', 'Especialidades', 'Laboratorio', 'Farmacia', 'Imágenes', 'Emergencia'],
            'III' => ['Medicina general', 'Especialidades', 'Laboratorio', 'Farmacia', 'Imágenes', 'Emergencia'],
        ];

        DB::table('ipress')->orderBy('id')->get()->each(function (object $ipress) use ($serviciosPorNivel): void {
            $nivel = strtoupper(trim((string) $ipress->nivel));
            $tieneEmergencia = in_array($nivel, ['II', 'III'], true);

            DB::table('ipress')
                ->where('id', $ipress->id)
                ->update([
                    'tiene_emergencia' => $tieneEmergencia,
                    'servicios' => json_encode($serviciosPorNivel[$nivel] ?? [], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('ipress', function (Blueprint $table): void {
            $table->dropColumn(['tiene_emergencia', 'servicios']);
        });
    }
};
