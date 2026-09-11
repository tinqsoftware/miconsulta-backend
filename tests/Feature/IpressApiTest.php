<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IpressApiTest extends TestCase
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
            $table->string('codigo_renipress')->nullable();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('nivel')->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->string('horario_atencion')->nullable();
            $table->boolean('esta_activa')->default(true);
            $table->boolean('tiene_emergencia')->default(false);
            $table->json('servicios')->nullable();
            $table->timestamps();
        });

        Schema::create('pacientes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->string('nombres');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('tipo_seguro')->nullable();
            $table->string('celular')->nullable();
            $table->string('direccion')->nullable();
            $table->string('distrito')->nullable();
            $table->unsignedBigInteger('id_ipress_asignada')->nullable();
            $table->timestamps();
        });

        Schema::create('validaciones_celular', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->timestamp('verificado_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_active_ipress_endpoint_excludes_inactive_locations(): void
    {
        DB::table('ipress')->insert([
            [
                'nombre' => 'Hospital activo',
                'nivel' => 'III',
                'latitud' => -12.05,
                'longitud' => -77.04,
                'esta_activa' => true,
                'tiene_emergencia' => true,
                'servicios' => json_encode(['Emergencia']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'IPRESS cerrada',
                'nivel' => 'I',
                'latitud' => null,
                'longitud' => null,
                'esta_activa' => false,
                'tiene_emergencia' => false,
                'servicios' => json_encode(['Farmacia']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->usuario(), 'sanctum')
            ->getJson('/api/v1/ipress/activas');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Hospital activo')
            ->assertJsonPath('data.0.tiene_emergencia', true)
            ->assertJsonPath('data.0.servicios.0', 'Emergencia');
    }

    public function test_profile_includes_assigned_ipress_services_and_emergency(): void
    {
        $ipressId = DB::table('ipress')->insertGetId([
            'nombre' => 'IPRESS asignada',
            'direccion' => 'Av. Salud 123',
            'telefono' => '999999999',
            'nivel' => 'II',
            'latitud' => -12.05,
            'longitud' => -77.04,
            'horario_atencion' => '24 horas',
            'esta_activa' => true,
            'tiene_emergencia' => true,
            'servicios' => json_encode(['Laboratorio', 'Emergencia']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pacientes')->insert([
            'id_usuario' => 10,
            'nombres' => 'Paciente',
            'apellido_paterno' => 'Demo',
            'id_ipress_asignada' => $ipressId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->usuario(10), 'sanctum')
            ->getJson('/api/v1/perfil');

        $response
            ->assertOk()
            ->assertJsonPath('ipress.nivel', 'II')
            ->assertJsonPath('ipress.tiene_emergencia', true)
            ->assertJsonPath('ipress.servicios.0', 'Laboratorio');
    }

    private function usuario(int $id = 1): Usuario
    {
        $usuario = new Usuario();
        $usuario->id = $id;
        $usuario->exists = true;

        return $usuario;
    }
}
