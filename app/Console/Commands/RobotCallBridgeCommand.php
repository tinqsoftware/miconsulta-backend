<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class RobotCallBridgeCommand extends Command
{
    protected $signature = 'robotcall:bridge {--once : Procesa una sola tanda y termina}';

    protected $description = 'Transporta llamadas pendientes del VPS hacia CENATE por la red interna';

    public function handle(): int
    {
        if (!(bool) config('services.robot_call.bridge_enabled')) {
            $this->warn('El bridge está desactivado. Configura ROBOT_CALL_BRIDGE_ENABLED=true.');
            return self::SUCCESS;
        }

        foreach (['cloud_url', 'cloud_token', 'cenate_url', 'cenate_key'] as $key) {
            if (!config('services.robot_call.' . $key)) {
                $this->error('Falta configurar ROBOT_CALL_' . strtoupper($key) . '.');
                return self::FAILURE;
            }
        }

        $bridgeId = gethostname() . '-' . Str::lower(Str::random(8));
        $this->info("Bridge activo: {$bridgeId}");

        do {
            $processed = $this->processBatch($bridgeId);
            if ($this->option('once')) {
                return $processed === false ? self::FAILURE : self::SUCCESS;
            }

            sleep(max(2, (int) config('services.robot_call.bridge_poll_seconds', 10)));
        } while (true);
    }

    private function processBatch(string $bridgeId): int|false
    {
        $cloud = config('services.robot_call.cloud_url');

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-Robot-Bridge-Token' => config('services.robot_call.cloud_token')])
                ->timeout(15)
                ->post($cloud . '/api/v1/robot-call-bridge/claim', [
                    'bridge_id' => $bridgeId,
                    'limit' => 10,
                ]);

            if (!$response->successful()) {
                $this->error('No se pudo reclamar la cola del VPS: HTTP ' . $response->status());
                return false;
            }

            $jobs = $response->json('data', []);
            foreach ($jobs as $job) {
                $this->sendJob($cloud, $bridgeId, $job);
            }

            if (count($jobs) > 0) {
                $this->info('Trabajos procesados: ' . count($jobs));
            }

            return count($jobs);
        } catch (Throwable $exception) {
            $this->error('Error de conexión con el VPS: ' . $exception->getMessage());
            return false;
        }
    }

    private function sendJob(string $cloud, string $bridgeId, array $job): void
    {
        $payload = $job['payload'] ?? [];
        $remoteUrl = config('services.robot_call.cenate_url') . '/api/softphone/robot-call/bridge';

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-Robot-Bridge-Key' => config('services.robot_call.cenate_key'),
                    'X-Idempotency-Key' => $job['idempotency_key'],
                ])
                ->timeout(20)
                ->post($remoteUrl, [
                    'externalId' => $job['idempotency_key'],
                    'number' => $job['celular'],
                    'name' => $payload['name'] ?? 'Paciente',
                    'time' => $payload['time'] ?? now()->format('H:i'),
                    'message' => $payload['message'] ?? null,
                    'tipo' => $job['tipo'],
                ]);

            if (!$response->successful()) {
                $this->report($cloud, $bridgeId, $job['id'], 'FALLIDA', null, 'CENATE respondió HTTP ' . $response->status());
                return;
            }

            $remoteJobId = $response->json('jobId');
            $remoteStatus = $this->waitForRemoteResult($remoteUrl, $remoteJobId);
            if ($remoteStatus === 'SUCCESS') {
                $this->report($cloud, $bridgeId, $job['id'], 'COMPLETADA', $remoteJobId, null);
            } elseif ($remoteStatus === 'FAILED') {
                $this->report($cloud, $bridgeId, $job['id'], 'FALLIDA', $remoteJobId, 'CENATE marcó la llamada como fallida.');
            } else {
                // Si CENATE sigue procesando después del timeout del bridge,
                // se conserva como ENVIADA: el externalId impide duplicarla.
                $this->report($cloud, $bridgeId, $job['id'], 'ENVIADA', $remoteJobId, null);
            }
        } catch (Throwable $exception) {
            $this->report($cloud, $bridgeId, $job['id'], 'FALLIDA', null, $exception->getMessage());
        }
    }

    private function waitForRemoteResult(string $postUrl, ?string $jobId): ?string
    {
        if (!$jobId) {
            return null;
        }

        $statusUrl = dirname($postUrl) . '/' . rawurlencode($jobId);
        $deadline = microtime(true) + 95;
        do {
            sleep(5);
            try {
                $response = Http::acceptJson()
                    ->withHeaders(['X-Robot-Bridge-Key' => config('services.robot_call.cenate_key')])
                    ->timeout(15)
                    ->get($statusUrl);
                if ($response->successful()) {
                    $status = strtoupper((string) $response->json('status'));
                    if (in_array($status, ['SUCCESS', 'FAILED'], true)) {
                        return $status;
                    }
                }
            } catch (Throwable $exception) {
                $this->warn('No se pudo consultar el estado de CENATE: ' . $exception->getMessage());
                return null;
            }
        } while (microtime(true) < $deadline);

        return null;
    }

    private function report(string $cloud, string $bridgeId, string $id, string $estado, ?string $remoteJobId, ?string $error): void
    {
        try {
            Http::acceptJson()
                ->asJson()
                ->withHeaders(['X-Robot-Bridge-Token' => config('services.robot_call.cloud_token')])
                ->timeout(15)
                ->post($cloud . '/api/v1/robot-call-bridge/result', [
                    'id' => $id,
                    'bridge_id' => $bridgeId,
                    'estado' => $estado,
                    'job_id_remoto' => $remoteJobId,
                    'error' => $error,
                ]);
        } catch (Throwable $exception) {
            $this->error("No se pudo reportar el trabajo {$id}: {$exception->getMessage()}");
        }
    }
}
