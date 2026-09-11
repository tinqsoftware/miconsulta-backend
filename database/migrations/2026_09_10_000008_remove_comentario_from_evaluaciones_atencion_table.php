<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones_atencion', function (Blueprint $table): void {
            $table->dropColumn('comentario');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones_atencion', function (Blueprint $table): void {
            $table->text('comentario')->nullable()->after('puntuacion');
        });
    }
};
