<?php

namespace App\Console\Commands;

use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateDemoPatientCommand extends Command
{
    protected $signature = 'prototype:patient
        {dni : DNI de ocho dígitos que se consultará en Telecertificación}
        {--password= : Contraseña temporal para iniciar sesión en Mi Consulta}
        {--nombres=Paciente : Nombres de demostración}
        {--apellido-paterno=Demo : Apellido paterno de demostración}
        {--apellido-materno= : Apellido materno de demostración}';

    protected $description = 'Crea o actualiza un paciente de demostración vinculado a un DNI de Telecertificación';

    public function handle(): int
    {
        $dni = preg_replace('/\D+/', '', (string) $this->argument('dni'));
        $password = (string) $this->option('password');

        if (! is_string($dni) || ! preg_match('/^\d{8}$/', $dni)) {
            $this->error('El DNI debe tener exactamente ocho dígitos.');

            return self::INVALID;
        }

        if ($password === '') {
            $this->error('Indica una contraseña temporal mediante --password.');

            return self::INVALID;
        }

        DB::transaction(function () use ($dni, $password): void {
            $usuario = Usuario::firstOrNew(['dni' => $dni]);
            $usuario->fill([
                'correo' => $usuario->correo ?: "{$dni}@miconsulta.test",
                'esta_activo' => true,
            ]);
            $usuario->contrasena = Hash::make($password);
            $usuario->save();

            Paciente::updateOrCreate(
                ['id_usuario' => $usuario->id],
                [
                    'nombres' => (string) $this->option('nombres'),
                    'apellido_paterno' => (string) $this->option('apellido-paterno'),
                    'apellido_materno' => $this->option('apellido-materno') ?: null,
                ],
            );
        });

        $this->info("Paciente de demostración listo para el DNI {$dni}.");

        return self::SUCCESS;
    }
}
