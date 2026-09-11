<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RobotCallBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.robot_call.bridge_token' => 'bridge-test-token',
        ]);
        DB::purge('sqlite');

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
        });
    }

    public function test_bridge_claims_encrypted_payload_and_is_idempotent(): void
    {
        $this->postJson('/api/v1/robot-call-bridge/claim', [
            'bridge_id' => 'laptop-test',
            'limit' => 10,
        ], ['X-Robot-Bridge-Token' => 'wrong-token'])
            ->assertUnauthorized();

        app(\App\Services\RobotCallOutboxService::class)->enqueue(
            'validacion_celular',
            '987654321',
            ['name' => 'Paciente', 'message' => 'Código 1234'],
            'validation:one',
            now()->addMinutes(10),
        );

        $claim = $this->postJson('/api/v1/robot-call-bridge/claim', [
            'bridge_id' => 'laptop-test',
            'limit' => 10,
        ], ['X-Robot-Bridge-Token' => 'bridge-test-token']);

        $claim->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.celular', '987654321')
            ->assertJsonPath('data.0.payload.message', 'Código 1234');

        $id = $claim->json('data.0.id');

        $this->postJson('/api/v1/robot-call-bridge/result', [
            'id' => $id,
            'bridge_id' => 'laptop-test',
            'estado' => 'ENVIADA',
            'job_id_remoto' => 'cenate-job-1',
        ], ['X-Robot-Bridge-Token' => 'bridge-test-token'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'ENVIADA');

        $this->assertDatabaseHas('robot_call_outbox', [
            'id' => $id,
            'estado' => 'ENVIADA',
            'job_id_remoto' => 'cenate-job-1',
        ]);

        $this->postJson('/api/v1/robot-call-bridge/claim', [
            'bridge_id' => 'laptop-test',
            'limit' => 10,
        ], ['X-Robot-Bridge-Token' => 'bridge-test-token'])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
