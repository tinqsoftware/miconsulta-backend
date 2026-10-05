<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TelecertificacionService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificadoController extends Controller
{
    /**
     * Streams the disability certificate of the authenticated patient.
     *
     * The client cannot choose a file path. The backend derives the filename
     * from the DNI and reads it from a read-only mounted file-server volume.
     */
    public function show(Request $request): StreamedResponse
    {
        abort_unless(config('certificates.enabled'), 404);
        abort_unless($request->user()?->paciente, 403);

        return $this->showForDni((string) $request->user()->dni, $request->user()->id);
    }

    /**
     * Returns a five-minute signed URL so a browser/PDF viewer can open the
     * document without receiving the Sanctum token in its URL or headers.
     */
    public function link(Request $request): JsonResponse
    {
        abort_unless(config('certificates.enabled'), 404);
        abort_unless($request->user()?->paciente, 403);

        $dni = $this->normalizedDni((string) $request->user()->dni);
        abort_if($dni === '', 404);

        // Do not issue a QR/signed URL that will immediately lead to a 404.
        // This is also what lets the Flutter client distinguish a patient
        // with an active certificate from one without one.
        $this->ensureCertificateAvailable($dni, $request->user()->id);

        $expiresAt = now()->addMinutes(5);

        return response()->json([
            'data' => [
                'url' => URL::temporarySignedRoute('certificados.discapacidad.archivo', $expiresAt, ['dni' => $dni]),
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }

    /**
     * The signature authorizes one exact DNI for five minutes. This endpoint
     * is deliberately separate from the bearer-token endpoint for Flutter Web
     * and native PDF viewers, which cannot attach an Authorization header.
     */
    public function showSigned(Request $request, string $dni): StreamedResponse
    {
        abort_unless(config('certificates.enabled'), 404);
        abort_unless($request->hasValidSignature(), 401);

        return $this->showForDni($dni);
    }

    private function showForDni(string $dni, ?int $userId = null): StreamedResponse
    {
        $dni = $this->normalizedDni($dni);
        abort_if($dni === '', 404);

        if (config('certificates.provider') === 'telecertificacion') {
            return $this->showFromTelecertificacion($dni, $userId, app(TelecertificacionService::class));
        }

        return $this->showFromFilesystem($dni);
    }

    /**
     * Confirms the certificate exists before we generate a browser-facing
     * signed URL. The actual PDF is retrieved again by the signed endpoint so
     * it is never cached in the user's session or URL.
     */
    private function ensureCertificateAvailable(string $dni, ?int $userId = null): void
    {
        if (config('certificates.provider') === 'telecertificacion') {
            try {
                abort_unless(app(TelecertificacionService::class)->certificateForDni($dni), 404);
            } catch (RequestException|RuntimeException $exception) {
                Log::warning('Could not verify disability certificate availability from Telecertificación.', [
                    'user_id' => $userId,
                    'exception' => $exception->getMessage(),
                ]);

                abort(503, 'El certificado no está disponible temporalmente.');
            }

            return;
        }

        $filename = str_replace('{dni}', $dni, (string) config('certificates.filename_pattern'));
        if (! $this->isSafePdfFilename($filename)) {
            Log::error('Invalid certificate filename pattern configuration.');
            abort(500, 'La configuración del certificado no es válida.');
        }

        abort_unless(Storage::disk((string) config('certificates.disk'))->exists($filename), 404);
    }

    private function showFromFilesystem(string $dni): StreamedResponse
    {
        $filename = str_replace('{dni}', $dni, (string) config('certificates.filename_pattern'));

        if (! $this->isSafePdfFilename($filename)) {
            Log::error('Invalid certificate filename pattern configuration.');
            abort(500, 'La configuración del certificado no es válida.');
        }

        $disk = Storage::disk((string) config('certificates.disk'));
        abort_unless($disk->exists($filename), 404);

        $stream = $disk->readStream($filename);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="certificado-discapacidad.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function showFromTelecertificacion(string $dni, ?int $userId, TelecertificacionService $telecertificacion): StreamedResponse
    {
        try {
            $certificate = $telecertificacion->certificateForDni($dni);
            abort_unless($certificate, 404);

            $pdf = $telecertificacion->download($certificate['url_archivo']);
        } catch (RequestException|RuntimeException $exception) {
            Log::warning('Could not retrieve disability certificate from Telecertificación.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            abort(503, 'El certificado no está disponible temporalmente.');
        }

        return response()->stream(static function () use ($pdf): void {
            echo $pdf;
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addcslashes($certificate['nombre_archivo'], '"\\') . '"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function normalizedDni(string $dni): string
    {
        $dni = preg_replace('/\D+/', '', $dni);

        return is_string($dni) && preg_match('/^\d{8}$/', $dni) ? $dni : '';
    }

    private function isSafePdfFilename(string $filename): bool
    {
        return $filename !== ''
            && ! str_contains($filename, '/')
            && ! str_contains($filename, '\\')
            && ! str_contains($filename, '..')
            && str_ends_with(strtolower($filename), '.pdf');
    }
}
