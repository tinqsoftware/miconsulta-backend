<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DesercionMotivosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('pacientes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
        });
        Schema::create('citas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('id_profesional')->nullable();
            $table->unsignedBigInteger('id_especialidad')->nullable();
            $table->date('fecha');
            $table->time('hora');
            $table->string('tipo_cita')->default('telemedicina');
        });
        Schema::create('deserciones_atencion', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_cita');
            $table->unsignedBigInteger('id_paciente');
            $table->string('token_acceso', 64);
            $table->string('motivo_codigo')->nullable();
            $table->text('motivo_descripcion')->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_desercion_exposes_exactly_the_seven_requested_motives(): void
    {
        DB::table('pacientes')->insert(['id' => 1, 'id_usuario' => 10]);
        DB::table('citas')->insert([
            'id' => 1,
            'id_paciente' => 1,
            'fecha' => '2026-09-11',
            'hora' => '10:00:00',
            'tipo_cita' => 'telemedicina',
        ]);
        DB::table('deserciones_atencion')->insert([
            'id_cita' => 1,
            'id_paciente' => 1,
            'token_acceso' => 'token-demo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->usuario(), 'sanctum')
            ->getJson('/api/v1/deserciones/citas/1?token=token-demo');

        $response
            ->assertOk()
            ->assertJsonCount(7, 'data.motivos')
            ->assertJsonPath('data.motivos.0.codigo', 'empeoramiento_emergencia')
            ->assertJsonPath('data.motivos.0.descripcion', 'Empeoramiento de molestias y atención por emergencia')
            ->assertJsonPath('data.motivos.1.descripcion', 'Conflicto laboral o académico')
            ->assertJsonPath('data.motivos.2.descripcion', 'Imprevisto familiar o personal')
            ->assertJsonPath('data.motivos.3.descripcion', 'Confusión con el horario')
            ->assertJsonPath('data.motivos.4.descripcion', 'Problemas de conexión a internet')
            ->assertJsonPath('data.motivos.5.descripcion', 'Dificultad para usar la app')
            ->assertJsonPath('data.motivos.6.descripcion', 'Falla del dispositivo');
    }

    private function usuario(): Usuario
    {
        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->exists = true;

        return $usuario;
    }
}
