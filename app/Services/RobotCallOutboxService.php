<?php

namespace App\Services;

use App\Models\RobotCallOutbox;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class RobotCallOutboxService
{
    public function enqueue(
        string $tipo,
        string $celular,
        array $payload,
        ?string $idempotencyKey = null,
        $expiraAt = null,
    ): ?RobotCallOutbox {
        $celular = $this->normalizePhone($celular);
        if ($celular === '') {
            return null;
        }

        $idempotencyKey ??= (string) Str::uuid();

        return RobotCallOutbox::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'id' => (string) Str::uuid(),
                'tipo' => $tipo,
                'celular' => $celular,
                'payload_cifrado' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
                'estado' => 'PENDIENTE',
                'intentos' => 0,
                'expira_at' => $expiraAt,
                'disponible_at' => now(),
            ],
        );
    }

    public function decodePayload(RobotCallOutbox $job): array
    {
        return json_decode(Crypt::decryptString($job->payload_cifrado), true, 512, JSON_THROW_ON_ERROR);
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '51') && strlen($digits) === 11) {
            $digits = substr($digits, 2);
        }

        return preg_match('/^9\d{8}$/', $digits) === 1 ? $digits : '';
    }
}
