<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EvaluacionAtencionTest extends TestCase
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
        Schema::create('profesionales', function (Blueprint $table): void {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
        });
        Schema::create('especialidades', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
        });
        Schema::create('ipress', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
        });
        Schema::create('citas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('id_profesional')->nullable();
            $table->unsignedBigInteger('id_especialidad')->nullable();
            $table->unsignedBigInteger('id_ipress')->nullable();
            $table->string('tipo_cita')->default('telemedicina');
            $table->date('fecha');
            $table->time('hora');
        });
        Schema::create('evaluaciones_atencion', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_cita')->unique();
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('id_profesional')->nullable();
            $table->string('token_acceso', 64)->unique();
            $table->decimal('puntuacion', 2, 1)->nullable();
            $table->unsignedTinyInteger('conexion_puntualidad')->nullable();
            $table->unsignedTinyInteger('escucha_trato')->nullable();
            $table->unsignedTinyInteger('explicacion_diagnostico')->nullable();
            $table->unsignedTinyInteger('explicacion_tratamiento')->nullable();
            $table->unsignedTinyInteger('claridad_proximos_pasos')->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_evaluation_accepts_only_the_five_ratings_and_returns_no_comment(): void
    {
        DB::table('pacientes')->insert(['id' => 1, 'id_usuario' => 10]);
        DB::table('profesionales')->insert([
            'id' => 1,
            'nombres' => 'Médico',
            'apellidos' => 'Demo',
        ]);
        DB::table('especialidades')->insert(['id' => 1, 'nombre' => 'Medicina general']);
        DB::table('ipress')->insert(['id' => 1, 'nombre' => 'IPRESS demo']);
        DB::table('citas')->insert([
            'id' => 1,
            'id_paciente' => 1,
            'id_profesional' => 1,
            'id_especialidad' => 1,
            'id_ipress' => 1,
            'fecha' => '2026-09-10',
            'hora' => '10:00:00',
        ]);
        DB::table('evaluaciones_atencion')->insert([
            'id_cita' => 1,
            'id_paciente' => 1,
            'id_profesional' => 1,
            'token_acceso' => 'token-demo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->usuario(10), 'sanctum')
            ->postJson('/api/v1/evaluaciones/citas/1', [
                'token' => 'token-demo',
                'conexion_puntualidad' => 5,
                'escucha_trato' => 4,
                'explicacion_diagnostico' => 5,
                'explicacion_tratamiento' => 4,
                'claridad_proximos_pasos' => 5,
                'comentario' => 'Este campo debe ignorarse y no persistirse.',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.evaluacion.calificaciones.conexion_puntualidad', 5)
            ->assertJsonMissingPath('data.evaluacion.comentario');
        $this->assertDatabaseHas('evaluaciones_atencion', [
            'id' => 1,
            'conexion_puntualidad' => 5,
            'claridad_proximos_pasos' => 5,
        ]);
    }

    private function usuario(int $id): Usuario
    {
        $usuario = new Usuario();
        $usuario->id = $id;
        $usuario->exists = true;

        return $usuario;
    }
}
