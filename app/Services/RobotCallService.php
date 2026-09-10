<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Adaptador al Robot Call de CENATE.
 *
 * El servicio de telefonía se mantiene fuera de este proyecto. Por ello esta
 * clase solo se activa cuando el VPS tiene ROBOT_CALL_ENABLED=true y la URL +
 * token del servicio configurados. Así ningún entorno local genera llamadas.
 */
class RobotCallService
{
    public function encuesta(): bool
    {
        return $this->encolar(
            'Encuesta de satisfacción',
            'Ingresa a EsSalud Digital para contarnos cómo fue tu atención. Tu opinión nos ayuda a mejorar.'
        );
    }

    public function desercion(): bool
    {
        return $this->encolar(
            'Registro de deserción',
            'Ingresa a EsSalud Digital para registrar el motivo por el que no pudiste completar tu atención.'
        );
    }

    public function validarCelular(string $celular, string $codigo): bool
    {
        return $this->encolar(
            'Validación de celular',
            "Tu código de validación de EsSalud Digital es {$codigo}. Repito: {$codigo}.",
            $celular
        );
    }

    private function encolar(string $name, string $message, ?string $number = null): bool
    {
        $enabled = (bool) config('services.robot_call.enabled');
        $url = config('services.robot_call.url');
        $number ??= config('services.robot_call.target_number');

        if (!$enabled || !$url || !$number) {
            Log::info('Robot Call omitido: integración no configurada.', [
                'event' => $name,
                'enabled' => $enabled,
            ]);
            return false;
        }

        try {
            $request = Http::acceptJson()->asJson()->timeout(10);
            if ($token = config('services.robot_call.token')) {
                $request = $request->withToken($token);
            }

            $response = $request->post($url, [
                // Campos admitidos por el endpoint RobotController de CENATE.
                'number' => $number,
                'name' => $name,
                'time' => now()->format('H:i'),
                // Se envía también para los despliegues del robot que admiten
                // plantillas dinámicas. Las versiones anteriores lo ignoran.
                'message' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Robot Call rechazó la solicitud.', [
                'event' => $name,
                'status' => $response->status(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo solicitar Robot Call.', [
                'event' => $name,
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }
}
