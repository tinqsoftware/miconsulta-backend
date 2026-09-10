<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validaciones_celular', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->string('celular', 15);
            $table->string('codigo_hash');
            $table->enum('canal', ['push', 'llamada']);
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('expira_at');
            $table->timestamp('verificado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validaciones_celular');
    }
};
