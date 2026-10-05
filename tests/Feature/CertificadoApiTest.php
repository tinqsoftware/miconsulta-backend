<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificadoApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'certificates.enabled' => true,
            'certificates.disk' => 'certificates',
            'certificates.filename_pattern' => '{dni}.pdf',
        ]);
        DB::purge('sqlite');

        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('dni')->unique();
            $table->string('contrasena');
            $table->string('correo')->nullable();
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();
        });
        Schema::create('pacientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('id_usuario');
            $table->string('nombres');
            $table->timestamps();
        });
    }

    public function test_authenticated_patient_can_view_only_own_certificate(): void
    {
        Storage::fake('certificates');
        Storage::disk('certificates')->put('12345678.pdf', '%PDF-1.4 certificate');
        Storage::disk('certificates')->put('87654321.pdf', '%PDF-1.4 another-patient');

        $usuario = Usuario::create([
            'dni' => '12345678',
            'contrasena' => 'not-used-in-this-test',
            'esta_activo' => true,
        ]);
        Paciente::create(['id_usuario' => $usuario->id, 'nombres' => 'Paciente']);

        $response = $this->actingAs($usuario, 'sanctum')
            ->get('/api/v1/certificados/discapacidad');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'inline; filename="certificado-discapacidad.pdf"');
        $this->assertSame('%PDF-1.4 certificate', $response->streamedContent());
    }

    public function test_certificate_requires_authentication(): void
    {
        $this->getJson('/api/v1/certificados/discapacidad')->assertUnauthorized();
    }

    public function test_certificate_api_returns_json_401_without_an_accept_header(): void
    {
        $this->get('/api/v1/certificados/discapacidad')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_certificate_is_retrieved_from_telecertificacion_by_authenticated_dni(): void
    {
        config([
            'certificates.provider' => 'telecertificacion',
            'certificates.telecertificacion.base_url' => 'http://telecert.test',
            'certificates.telecertificacion.token' => 'internal-token',
        ]);
        Http::fake([
            'http://telecert.test/api/telecertificacion/certificados*' => Http::response([
                [
                    'dni_paciente' => '12345678',
                    'activo' => true,
                    'fecha_termino' => now()->addYear()->toDateString(),
                    'url_archivo' => '/api/files/certificados/12345678.pdf',
                    'nombre_archivo' => '12345678.pdf',
                ],
            ]),
            'http://telecert.test/api/files/certificados/12345678.pdf' => Http::response('%PDF-1.4 certificate'),
        ]);

        $usuario = Usuario::create([
            'dni' => '12345678',
            'contrasena' => 'not-used-in-this-test',
            'esta_activo' => true,
        ]);
        Paciente::create(['id_usuario' => $usuario->id, 'nombres' => 'Paciente']);

        $response = $this->actingAs($usuario, 'sanctum')
            ->get('/api/v1/certificados/discapacidad');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'inline; filename="12345678.pdf"');
        $this->assertSame('%PDF-1.4 certificate', $response->streamedContent());

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://telecert.test/api/telecertificacion/certificados?dni=12345678'
                && $request->hasHeader('Authorization', 'Bearer internal-token');
        });
    }

    public function test_authenticated_patient_receives_a_temporary_signed_pdf_link(): void
    {
        config([
            'certificates.provider' => 'telecertificacion',
            'certificates.telecertificacion.base_url' => 'http://telecert.test',
            'certificates.telecertificacion.token' => 'internal-token',
        ]);
        Http::fake([
            'http://telecert.test/api/telecertificacion/certificados*' => Http::response([
                [
                    'activo' => true,
                    'fecha_termino' => now()->addYear()->toDateString(),
                    'url_archivo' => '/api/files/certificados/12345678.pdf',
                    'nombre_archivo' => '12345678.pdf',
                ],
            ]),
            'http://telecert.test/api/files/certificados/12345678.pdf' => Http::response('%PDF-1.4 certificate'),
        ]);

        $usuario = Usuario::create([
            'dni' => '12345678',
            'contrasena' => 'not-used-in-this-test',
            'esta_activo' => true,
        ]);
        Paciente::create(['id_usuario' => $usuario->id, 'nombres' => 'Paciente']);

        $linkResponse = $this->actingAs($usuario, 'sanctum')
            ->getJson('/api/v1/certificados/discapacidad/enlace')
            ->assertOk()
            ->assertJsonPath('data.expires_at', now()->addMinutes(5)->toIso8601String());

        $this->get($linkResponse->json('data.url'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
