<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

        $dni = preg_replace('/\D+/', '', (string) $request->user()->dni);
        abort_if($dni === '', 404);

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

    private function isSafePdfFilename(string $filename): bool
    {
        return $filename !== ''
            && ! str_contains($filename, '/')
            && ! str_contains($filename, '\\')
            && ! str_contains($filename, '..')
            && str_ends_with(strtolower($filename), '.pdf');
    }
}
