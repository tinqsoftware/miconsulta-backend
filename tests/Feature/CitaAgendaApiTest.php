<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CitaAgendaApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('ipress', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::create('especialidades', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::create('pacientes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_ipress_asignada')->nullable();
            $table->timestamps();
        });

        Schema::create('profesionales', function (Blueprint $table): void {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
            $table->unsignedBigInteger('id_especialidad');
            $table->unsignedBigInteger('id_ipress');
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();
        });

        Schema::create('horarios_disponibles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_profesional');
            $table->unsignedBigInteger('id_especialidad');
            $table->unsignedBigInteger('id_ipress');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('tipo_cita');
            $table->unsignedInteger('cupo_maximo')->default(1);
            $table->unsignedInteger('cupo_ocupado')->default(0);
            $table->boolean('esta_disponible')->default(true);
            $table->timestamps();
        });
    }

    public function test_professionals_endpoint_and_selected_agenda_only_return_active_slots(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 06:00:00', 'America/Lima'));

        DB::table('ipress')->insert(['id' => 1, 'nombre' => 'IPRESS demo']);
        DB::table('especialidades')->insert(['id' => 1, 'nombre' => 'Medicina General']);
        DB::table('pacientes')->insert([
            'id' => 1,
            'id_usuario' => 10,
            'id_ipress_asignada' => 1,
        ]);
        DB::table('profesionales')->insert([
            [
                'id' => 1,
                'nombres' => 'Ana',
                'apellidos' => 'Activa',
                'id_especialidad' => 1,
                'id_ipress' => 1,
                'esta_activo' => true,
            ],
            [
                'id' => 2,
                'nombres' => 'Luis',
                'apellidos' => 'Inactivo',
                'id_especialidad' => 1,
                'id_ipress' => 1,
                'esta_activo' => false,
            ],
        ]);

        $profesionales = $this->actingAs($this->usuario(), 'sanctum')
            ->getJson('/api/v1/citas/profesionales?tipo_cita=telemedicina&id_especialidad=1');

        $profesionales
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.nombres', 'Ana');

        $agenda = $this->actingAs($this->usuario(), 'sanctum')
            ->getJson('/api/v1/citas/agenda?tipo_cita=telemedicina&id_especialidad=1&id_profesional=1');

        $agenda
            ->assertOk()
            ->assertJsonPath('data.fechas.0', '2026-09-11')
            ->assertJsonPath('data.horarios.0.fecha', '2026-09-11')
            ->assertJsonPath('data.horarios.0.hora_inicio', '07:00:00');

        Carbon::setTestNow();
    }

    private function usuario(): Usuario
    {
        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->exists = true;

        return $usuario;
    }
}
