<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RobotCallOutbox;
use App\Services\RobotCallOutboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RobotCallBridgeController extends Controller
{
    public function __construct(private readonly RobotCallOutboxService $outbox)
    {
    }

    public function claim(Request $request)
    {
        if (!$this->validToken($request)) {
            return response()->json(['message' => 'Bridge no autorizado.'], 401);
        }

        $data = $request->validate([
            'bridge_id' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $bridgeId = $data['bridge_id'];
        $limit = $data['limit'] ?? 10;
        $leaseSeconds = (int) config('services.robot_call.bridge_lease_seconds', 120);

        $jobs = DB::transaction(function () use ($bridgeId, $limit, $leaseSeconds): array {
            $now = now();

            RobotCallOutbox::query()
                ->where('estado', 'TOMADA')
                ->whereNotNull('lease_hasta')
                ->where('lease_hasta', '<', $now)
                ->update([
                    'estado' => 'PENDIENTE',
                    'bridge_id' => null,
                    'lease_hasta' => null,
                    'disponible_at' => $now,
                    'ultimo_error' => 'Lease recuperado después de desconexión del bridge.',
                    'updated_at' => $now,
                ]);

            RobotCallOutbox::query()
                ->whereIn('estado', ['PENDIENTE', 'TOMADA'])
                ->whereNotNull('expira_at')
                ->where('expira_at', '<=', $now)
                ->update([
                    'estado' => 'EXPIRADA',
                    'lease_hasta' => null,
                    'updated_at' => $now,
                ]);

            $selected = RobotCallOutbox::query()
                ->where('estado', 'PENDIENTE')
                ->where(function ($query) use ($now): void {
                    $query->whereNull('disponible_at')->orWhere('disponible_at', '<=', $now);
                })
                ->where(function ($query) use ($now): void {
                    $query->whereNull('expira_at')->orWhere('expira_at', '>', $now);
                })
                ->where('intentos', '<', (int) config('services.robot_call.bridge_max_attempts', 5))
                ->orderBy('created_at')
                ->lockForUpdate()
                ->limit($limit)
                ->get();

            foreach ($selected as $job) {
                $job->update([
                    'estado' => 'TOMADA',
                    'bridge_id' => $bridgeId,
                    'lease_hasta' => $now->copy()->addSeconds($leaseSeconds),
                    'intentos' => $job->intentos + 1,
                ]);
            }

            return $selected->map(function (RobotCallOutbox $job): array {
                return [
                    'id' => $job->id,
                    'idempotency_key' => $job->idempotency_key,
                    'tipo' => $job->tipo,
                    'celular' => $job->celular,
                    'payload' => $this->outbox->decodePayload($job),
                    'expira_at' => $job->expira_at?->toIso8601String(),
                    'intentos' => $job->intentos,
                ];
            })->all();
        });

        return response()->json(['data' => $jobs]);
    }

    public function result(Request $request)
    {
        if (!$this->validToken($request)) {
            return response()->json(['message' => 'Bridge no autorizado.'], 401);
        }

        $data = $request->validate([
            'id' => ['required', 'uuid'],
            'bridge_id' => ['required', 'string', 'max:100'],
            'estado' => ['required', 'in:ENVIADA,COMPLETADA,FALLIDA'],
            'job_id_remoto' => ['nullable', 'string', 'max:100'],
            'error' => ['nullable', 'string', 'max:1000'],
        ]);

        $job = RobotCallOutbox::find($data['id']);
        if (!$job || $job->bridge_id !== $data['bridge_id']) {
            return response()->json(['message' => 'Trabajo no encontrado o lease inválido.'], 409);
        }

        $estado = $data['estado'];
        if ($estado === 'FALLIDA') {
            $expired = $job->expira_at?->isPast() ?? false;
            $retryable = !$expired && $job->intentos < (int) config('services.robot_call.bridge_max_attempts', 5);
            $job->update([
                'estado' => $retryable ? 'PENDIENTE' : ($expired ? 'EXPIRADA' : 'FALLIDA'),
                'disponible_at' => $retryable ? now()->addSeconds($this->backoffSeconds($job->intentos)) : null,
                'lease_hasta' => null,
                'bridge_id' => null,
                'ultimo_error' => $data['error'] ?? 'El bridge no pudo enviar la llamada.',
            ]);
        } else {
            $job->update([
                'estado' => $estado,
                'job_id_remoto' => $data['job_id_remoto'] ?? $job->job_id_remoto,
                'lease_hasta' => null,
                'bridge_id' => null,
                'ultimo_error' => null,
            ]);
        }

        return response()->json(['data' => ['id' => $job->id, 'estado' => $job->estado]]);
    }

    private function validToken(Request $request): bool
    {
        $configured = (string) config('services.robot_call.bridge_token');
        $provided = (string) $request->header('X-Robot-Bridge-Token');

        return $configured !== '' && $provided !== '' && hash_equals($configured, $provided);
    }

    private function backoffSeconds(int $attempts): int
    {
        return min(900, max(30, 30 * (2 ** max(0, $attempts - 1))));
    }
}
