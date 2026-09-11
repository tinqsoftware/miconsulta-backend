<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('robot_call_outbox')) {
            return;
        }

        Schema::create('robot_call_outbox', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('idempotency_key', 128)->unique();
            $table->string('tipo', 40);
            $table->string('celular', 20);
            $table->text('payload_cifrado');
            $table->string('estado', 20)->default('PENDIENTE');
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->timestamp('expira_at')->nullable();
            $table->timestamp('disponible_at')->nullable();
            $table->timestamp('lease_hasta')->nullable();
            $table->string('bridge_id', 100)->nullable();
            $table->string('job_id_remoto', 100)->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamps();

            $table->index(['estado', 'disponible_at']);
            $table->index(['estado', 'lease_hasta']);
            $table->index('expira_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('robot_call_outbox');
    }
};
