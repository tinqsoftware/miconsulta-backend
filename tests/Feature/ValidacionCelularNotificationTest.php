<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Usuario;
use App\Services\FirebaseMessagingService;
use App\Services\RobotCallService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ValidacionCelularNotificationTest extends TestCase
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
        Schema::create('validaciones_celular', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_usuario')->unique();
            $table->string('celular', 15);
            $table->string('codigo_hash');
            $table->string('canal', 10);
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('expira_at');
            $table->timestamp('verificado_at')->nullable();
            $table->timestamps();
        });
        Schema::create('notificaciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_paciente');
            $table->string('tipo');
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('categoria')->nullable();
            $table->text('datos_extra')->nullable();
            $table->boolean('fue_leida')->default(false);
            $table->timestamp('fecha_envio')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->timestamps();
        });
    }

    public function test_sms_title_starts_with_code_and_call_remains_available(): void
    {
        $usuario = $this->usuarioConPaciente();
        $captured = [];

        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('send')
            ->once()
            ->withArgs(function (string $token, string $title, string $body) use (&$captured): bool {
                $captured = compact('token', 'title', 'body');

                return true;
            })
            ->andReturnTrue();
        $this->app->instance(FirebaseMessagingService::class, $firebase);

        $this->postJson('/api/v1/perfil/celular/solicitar-validacion', [
            'celular' => '987654321',
            'canal' => 'push',
        ])->assertOk();

        $this->assertMatchesRegularExpression(
            '/^\d{4} - Código SMS para validar tu celular$/u',
            $captured['title'],
        );
        $codigo = substr($captured['title'], 0, 4);
        $this->assertSame(
            "Te enviamos un SMS con tu código de validación: {$codigo}. Vence en 10 minutos.",
            $captured['body'],
        );
        $this->assertSame('fcm-test-token', $captured['token']);
        $this->assertDatabaseHas('notificaciones', [
            'id_paciente' => 1,
            'titulo' => $captured['title'],
            'mensaje' => $captured['body'],
        ]);

        $robotCall = Mockery::mock(RobotCallService::class);
        $robotCall->shouldReceive('validarCelular')
            ->once()
            ->with('987654321', Mockery::type('string'))
            ->andReturnTrue();
        $this->app->instance(RobotCallService::class, $robotCall);

        $this->postJson('/api/v1/perfil/celular/solicitar-validacion', [
            'celular' => '987654321',
            'canal' => 'llamada',
        ])->assertOk();
    }

    private function usuarioConPaciente(): Usuario
    {
        $paciente = new Paciente();
        $paciente->id = 1;
        $paciente->id_usuario = 10;
        $paciente->exists = true;

        $usuario = new Usuario();
        $usuario->id = 10;
        $usuario->token_fcm = 'fcm-test-token';
        $usuario->exists = true;
        $usuario->setRelation('paciente', $paciente);

        return tap($usuario, fn (Usuario $model) => $this->actingAs($model, 'sanctum'));
    }
}
