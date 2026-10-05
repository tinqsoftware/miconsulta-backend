<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelecertificacionService
{
    /**
     * @return array{url_archivo: string, nombre_archivo: string}|null
     */
    public function certificateForDni(string $dni): ?array
    {
        $response = $this->client()
            ->acceptJson()
            ->get('/api/telecertificacion/certificados', ['dni' => $dni]);

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();
        $items = Arr::wrap($response->json('data', $response->json()));

        foreach ($items as $item) {
            if (! is_array($item)
                || ! $this->matchesDni($item['dni_paciente'] ?? null, $dni)
                || ! ($item['activo'] ?? false)
                || ! isset($item['url_archivo'])) {
                continue;
            }

            if (! $this->isCurrent($item['fecha_termino'] ?? null)) {
                continue;
            }

            return [
                'url_archivo' => $this->safeFileUrl((string) $item['url_archivo']),
                'nombre_archivo' => $this->safeFilename((string) ($item['nombre_archivo'] ?? 'certificado-discapacidad.pdf')),
            ];
        }

        return null;
    }

    public function download(string $url): string
    {
        $response = $this->client()->get($url);
        $response->throw();

        $body = $response->body();
        $maxBytes = (int) config('certificates.telecertificacion.max_pdf_bytes');
        if ($body === '' || strlen($body) > $maxBytes || ! str_starts_with($body, '%PDF-')) {
            throw new RuntimeException('Telecertificación devolvió un archivo de certificado inválido.');
        }

        return $body;
    }

    private function client(): PendingRequest
    {
        $baseUrl = (string) config('certificates.telecertificacion.base_url');
        $token = (string) config('certificates.telecertificacion.token');

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('Telecertificación no está configurada.');
        }

        return Http::baseUrl($baseUrl)
            ->withToken($token)
            ->timeout((int) config('certificates.telecertificacion.timeout_seconds'))
            ->retry(2, 200, function ($exception): bool {
                return $exception instanceof ConnectionException;
            });
    }

    private function isCurrent(mixed $expiresAt): bool
    {
        if (! is_string($expiresAt) || $expiresAt === '') {
            return true;
        }

        return now()->startOfDay()->lte(Carbon::parse($expiresAt)->endOfDay());
    }

    private function safeFileUrl(string $url): string
    {
        $baseUrl = (string) config('certificates.telecertificacion.base_url');
        $base = parse_url($baseUrl);
        $candidate = parse_url($url);

        if ($candidate === false || $base === false) {
            throw new RuntimeException('Telecertificación devolvió una URL de archivo inválida.');
        }

        if (! isset($candidate['host'])) {
            return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }

        if (($candidate['scheme'] ?? '') !== ($base['scheme'] ?? '')
            || ($candidate['host'] ?? '') !== ($base['host'] ?? '')
            || $this->effectivePort($candidate) !== $this->effectivePort($base)) {
            throw new RuntimeException('Telecertificación devolvió una URL de archivo no permitida.');
        }

        return $url;
    }

    private function safeFilename(string $filename): string
    {
        $filename = basename($filename);

        return str_ends_with(strtolower($filename), '.pdf')
            ? $filename
            : 'certificado-discapacidad.pdf';
    }

    private function matchesDni(mixed $candidate, string $dni): bool
    {
        $normalized = preg_replace('/\\D+/', '', (string) $candidate);

        return is_string($normalized) && hash_equals($dni, $normalized);
    }

    /** @param array<string, mixed> $url */
    private function effectivePort(array $url): int
    {
        if (isset($url['port'])) {
            return (int) $url['port'];
        }

        return ($url['scheme'] ?? '') === 'https' ? 443 : 80;
    }
}
