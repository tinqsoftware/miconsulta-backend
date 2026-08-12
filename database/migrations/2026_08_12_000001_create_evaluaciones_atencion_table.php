<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cita')->unique()->constrained('citas')->cascadeOnDelete();
            $table->foreignId('id_paciente')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('id_profesional')->nullable()->constrained('profesionales')->nullOnDelete();
            $table->string('token_acceso', 64)->unique();
            $table->decimal('puntuacion', 2, 1)->nullable();
            $table->text('comentario')->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_atencion');
    }
};
