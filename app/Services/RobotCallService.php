<?php

namespace App\Services;

use App\Models\Cita;
use Illuminate\Support\Facades\Log;

/**
 * Productor de trabajos para el Robot Call de CENATE.
 *
 * MiConsulta nunca abre la conexión interna de CENATE desde la petición web:
 * cifra el mensaje y lo deja en la outbox para que el bridge local lo entregue.
 */
class RobotCallService
{
    public function encuesta(Cita $cita): bool
    {
        $cita->loadMissing('paciente');

        return $this->encolar(
            'Encuesta de satisfacción',
            'Ingresa a EsSalud Digital para contarnos cómo fue tu atención. Tu opinión nos ayuda a mejorar.',
            $cita->paciente?->celular,
            'encuesta_atencion',
            'encuesta:' . $cita->id,
            $this->nombrePaciente($cita),
        );
    }

    public function desercion(Cita $cita): bool
    {
        $cita->loadMissing('paciente');

        return $this->encolar(
            'Registro de deserción',
            'Ingresa a EsSalud Digital para registrar el motivo por el que no pudiste completar tu atención.',
            $cita->paciente?->celular,
            'desercion_atencion',
            'desercion:' . $cita->id,
            $this->nombrePaciente($cita),
        );
    }

    public function validarCelular(string $celular, string $codigo): bool
    {
        return $this->encolar(
            'Validación de celular',
            "Tu código de validación de EsSalud Digital es {$codigo}. Repito: {$codigo}.",
            $celular,
            'validacion_celular',
            hash('sha256', 'validacion:' . $celular . ':' . $codigo),
            'Paciente',
            now()->addMinutes(10),
        );
    }

    private function encolar(
        string $name,
        string $message,
        ?string $number,
        string $tipo,
        string $idempotencyKey,
        string $patientName,
        $expiresAt = null,
    ): bool
    {
        $normalizedNumber = app(RobotCallOutboxService::class)->normalizePhone((string) $number);
        if ($normalizedNumber === '') {
            Log::warning('Robot Call omitido: el paciente no tiene celular válido.', ['event' => $name]);
            return false;
        }

        return (bool) app(RobotCallOutboxService::class)->enqueue(
            $tipo,
            $normalizedNumber,
            [
                'name' => $patientName,
                'time' => now()->format('H:i'),
                'message' => $message,
                'tipo' => $tipo,
            ],
            $idempotencyKey,
            $expiresAt,
        );
    }

    private function nombrePaciente(Cita $cita): string
    {
        $paciente = $cita->paciente;
        return trim(implode(' ', array_filter([
            $paciente?->nombres,
            $paciente?->apellido_paterno,
            $paciente?->apellido_materno,
        ]))) ?: 'Paciente';
    }
}
