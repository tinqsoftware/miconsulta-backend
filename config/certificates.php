<?php

return [
    /*
    | The file server must be mounted read-only at CERTIFICATES_ROOT. Files
    | are matched by the authenticated user's DNI; paths are never accepted
    | from the client.
    */
    'enabled' => env('CERTIFICATES_ENABLED', false),
    'provider' => env('CERTIFICATES_PROVIDER', 'filesystem'),
    'disk' => env('CERTIFICATES_DISK', 'certificates'),
    'filename_pattern' => env('CERTIFICATES_FILENAME_PATTERN', '{dni}.pdf'),

    /*
    | Telecertificacion is the preferred provider in the EsSalud prototype.
    | Mi Consulta calls it server-to-server; its token and file URLs never
    | reach a mobile device or the browser.
    */
    'telecertificacion' => [
        'base_url' => rtrim((string) env('TELECERTIFICACION_BASE_URL', ''), '/'),
        'token' => env('TELECERTIFICACION_TOKEN'),
        // Prefer a service credential so the backend can renew its own short
        // lived bearer token. TELECERTIFICACION_TOKEN remains supported for
        // deployments where CENATE issues a non-expiring integration token.
        'username' => env('TELECERTIFICACION_USERNAME'),
        'password' => env('TELECERTIFICACION_PASSWORD'),
        'token_cache_seconds' => (int) env('TELECERTIFICACION_TOKEN_CACHE_SECONDS', 1200),
        'timeout_seconds' => (int) env('TELECERTIFICACION_TIMEOUT_SECONDS', 10),
        'max_pdf_bytes' => (int) env('TELECERTIFICACION_MAX_PDF_BYTES', 15 * 1024 * 1024),
    ],
];
