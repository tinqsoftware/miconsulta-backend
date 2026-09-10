<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deserciones_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cita')->unique()->constrained('citas')->cascadeOnDelete();
            $table->foreignId('id_paciente')->constrained('pacientes')->cascadeOnDelete();
            $table->string('token_acceso', 64)->unique();
            $table->string('motivo_codigo', 80)->nullable();
            $table->string('motivo_descripcion', 255)->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deserciones_atencion');
    }
};
