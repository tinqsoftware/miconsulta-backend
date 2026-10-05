<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Core schema that predates the feature migrations in this repository.
     *
     * Historic deployments created these tables by importing an untracked SQL
     * dump. Keeping this migration in Git makes an empty MySQL container
     * deployable and is deliberately idempotent for existing imports.
     */
    public function up(): void
    {
        if (Schema::hasTable('usuarios')) {
            // Legacy production dumps contain the clinical core tables but
            // predate these two application tables. They must exist before
            // the feature migrations below alter `banners`.
            if (!Schema::hasTable('banners')) {
                Schema::create('banners', function (Blueprint $table): void {
                    $table->id();
                    $table->string('titulo');
                    $table->string('imagen_url');
                    $table->string('link_url')->nullable();
                    $table->boolean('estado')->default(true);
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('configuraciones')) {
                Schema::create('configuraciones', function (Blueprint $table): void {
                    $table->id();
                    $table->string('clave')->unique();
                    $table->text('valor')->nullable();
                    $table->string('descripcion')->nullable();
                    $table->timestamps();
                });
            }

            return;
        }

        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('dni', 8)->unique();
            $table->string('contrasena');
            $table->string('correo')->unique();
            $table->boolean('esta_activo')->default(true);
            $table->string('token_fcm')->nullable();
            $table->boolean('usa_biometria')->default(false);
            $table->timestamp('ultimo_acceso')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('ipress', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_renipress', 20)->nullable()->unique();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('nivel', 10)->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->string('horario_atencion', 100)->nullable();
            $table->boolean('esta_activa')->default(true);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('especialidades', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('codigo', 10)->nullable()->index();
            $table->boolean('esta_activa')->default(true);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('pacientes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_usuario')->unique();
            $table->string('nombres', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['M', 'F'])->nullable();
            $table->string('tipo_seguro', 50)->nullable();
            $table->string('codigo_asegurado', 20)->nullable();
            $table->string('operador_celular', 50)->nullable();
            $table->string('celular', 15)->nullable();
            $table->string('telefono_fijo', 15)->nullable();
            $table->string('departamento', 50)->nullable();
            $table->string('provincia', 50)->nullable();
            $table->string('distrito', 50)->nullable();
            $table->string('tipo_via', 50)->nullable();
            $table->string('direccion')->nullable();
            $table->string('referencia')->nullable();
            $table->unsignedBigInteger('id_ipress_asignada')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('profesionales', function (Blueprint $table): void {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('numero_colegiatura', 20)->nullable()->index();
            $table->unsignedBigInteger('id_especialidad')->nullable()->index();
            $table->unsignedBigInteger('id_ipress')->nullable()->index();
            $table->boolean('esta_activo')->default(true);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('horarios_disponibles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_profesional')->index();
            $table->unsignedBigInteger('id_especialidad')->index();
            $table->unsignedBigInteger('id_ipress')->index();
            $table->date('fecha')->index();
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->enum('tipo_cita', ['presencial', 'telemedicina']);
            $table->unsignedInteger('cupo_maximo')->default(1);
            $table->unsignedInteger('cupo_ocupado')->default(0);
            $table->boolean('esta_disponible')->default(true);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('citas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_paciente')->index();
            $table->unsignedBigInteger('id_horario')->nullable()->index();
            $table->unsignedBigInteger('id_profesional')->nullable()->index();
            $table->unsignedBigInteger('id_especialidad')->index();
            $table->unsignedBigInteger('id_ipress')->index();
            $table->enum('tipo_cita', ['presencial', 'telemedicina']);
            $table->date('fecha')->index();
            $table->time('hora');
            $table->enum('estado', ['programada', 'confirmada', 'completada', 'cancelada', 'no_asistio', 'pendiente_programacion'])->default('programada')->index();
            $table->text('motivo_consulta')->nullable();
            $table->string('url_audio_sintomas')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('consentimientos', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo');
            $table->longText('contenido');
            $table->boolean('es_obligatorio')->default(true);
            $table->enum('tipo', ['privacidad', 'terminos', 'consentimiento_informado']);
            $table->string('version', 10)->default('1.0');
            $table->boolean('esta_activo')->default(true);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('consentimientos_cita', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_cita')->index();
            $table->unsignedBigInteger('id_consentimiento')->index();
            $table->unsignedBigInteger('id_paciente')->index();
            $table->boolean('fue_aceptado');
            $table->timestamp('fecha_aceptacion')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
            $table->unique(['id_cita', 'id_consentimiento', 'id_paciente']);
        });

        Schema::create('medicamentos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_generico');
            $table->string('nombre_comercial')->nullable();
            $table->string('forma_farmaceutica', 100)->nullable();
            $table->string('concentracion', 100)->nullable();
            $table->string('unidad_medida', 50)->nullable();
            $table->boolean('esta_activo')->default(true);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('recetas', function (Blueprint $table): void {
            $table->id();
            $table->string('numero_receta', 30)->nullable()->unique();
            $table->unsignedBigInteger('id_paciente')->index();
            $table->unsignedBigInteger('id_profesional')->nullable()->index();
            $table->unsignedBigInteger('id_ipress')->nullable()->index();
            $table->unsignedBigInteger('id_especialidad')->nullable()->index();
            $table->string('acto_medico', 100)->nullable();
            $table->string('numero_acto_medico', 20)->nullable();
            $table->date('fecha_emision')->index();
            $table->date('fecha_vigencia')->nullable();
            $table->enum('estado', ['vigente', 'dispensada', 'vencida', 'parcial'])->default('vigente')->index();
            $table->string('url_pdf')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('medicamentos_receta', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_receta')->index();
            $table->unsignedBigInteger('id_medicamento')->index();
            $table->decimal('cantidad', 10, 2);
            $table->unsignedInteger('dias')->default(1);
            $table->unsignedInteger('total_tomas')->default(1);
            $table->boolean('recordatorios_activados')->default(false);
            $table->string('unidad_formato', 50)->nullable();
            $table->text('indicacion')->nullable();
            $table->boolean('esta_disponible')->default(true);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('programacion_tomas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_medicamento_receta')->index();
            $table->unsignedBigInteger('id_paciente')->index();
            $table->date('fecha_inicio');
            $table->time('hora_inicio');
            $table->enum('tipo_frecuencia', ['cada_x_horas', 'veces_al_dia']);
            $table->unsignedInteger('valor_frecuencia');
            $table->decimal('cantidad_por_toma', 10, 2)->default(1);
            $table->unsignedInteger('duracion_dias')->nullable();
            $table->unsignedInteger('numero_tomas_total')->nullable();
            $table->boolean('esta_activa')->default(true);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('registro_tomas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_programacion')->index();
            $table->unsignedBigInteger('id_paciente')->index();
            $table->unsignedBigInteger('id_medicamento')->index();
            $table->date('fecha_programada')->index();
            $table->time('hora_programada');
            $table->date('fecha_real')->nullable();
            $table->time('hora_real')->nullable();
            $table->enum('estado', ['pendiente', 'tomada', 'pospuesta', 'omitida'])->default('pendiente')->index();
            $table->unsignedInteger('minutos_pospuestos')->nullable();
            $table->enum('periodo_dia', ['manana', 'tarde', 'noche']);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('notificaciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_paciente')->index();
            $table->enum('tipo', ['toma_programada', 'toma_registrada', 'toma_pospuesta', 'toma_olvidada', 'cita_confirmada', 'resultados_disponibles', 'recordatorio_cita', 'aviso_importante']);
            $table->string('titulo');
            $table->text('mensaje');
            $table->enum('categoria', ['tomas', 'importantes', 'general'])->default('general');
            $table->json('datos_extra')->nullable();
            $table->boolean('fue_leida')->default(false);
            $table->timestamp('fecha_envio');
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('atenciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_paciente')->index();
            $table->unsignedBigInteger('id_profesional')->nullable()->index();
            $table->unsignedBigInteger('id_especialidad')->nullable()->index();
            $table->unsignedBigInteger('id_ipress')->nullable()->index();
            $table->enum('categoria', ['consulta_externa', 'emergencia', 'hospitalizacion', 'centro_quirurgico'])->index();
            $table->date('fecha')->index();
            $table->text('diagnostico')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('paciente_farmacia', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_paciente')->unique();
            $table->enum('tipo_recojo', ['ipress', 'farmacia_vecina']);
            $table->unsignedBigInteger('id_ipress')->nullable()->index();
            $table->boolean('esta_registrado_farmacia')->default(false);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('codigos_verificacion', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_usuario_destino')->index();
            $table->string('codigo', 6);
            $table->enum('tipo', ['cambio_contrasena', 'olvide_contrasena']);
            $table->timestamp('expira_en')->index();
            $table->boolean('fue_usado')->default(false);
            $table->unsignedBigInteger('id_usuario')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('seguimiento_tomas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_receta')->index();
            $table->unsignedBigInteger('id_medicamentos_receta')->index();
            $table->timestamp('fecha_hora_programada')->index();
            $table->timestamp('fecha_hora_real')->nullable();
            $table->enum('estado', ['pendiente', 'tomada', 'pospuesta', 'omitida'])->default('pendiente')->index();
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo');
            $table->string('imagen_url');
            $table->string('link_url')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        Schema::create('configuraciones', function (Blueprint $table): void {
            $table->id();
            $table->string('clave')->unique();
            $table->text('valor')->nullable();
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'configuraciones', 'banners', 'seguimiento_tomas', 'codigos_verificacion',
            'paciente_farmacia', 'atenciones', 'notificaciones', 'registro_tomas',
            'programacion_tomas', 'medicamentos_receta', 'recetas', 'medicamentos',
            'consentimientos_cita', 'consentimientos', 'citas', 'horarios_disponibles',
            'profesionales', 'pacientes', 'especialidades', 'ipress', 'usuarios',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }
};
