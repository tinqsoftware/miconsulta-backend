<?php

return [
    /*
    | The file server must be mounted read-only at CERTIFICATES_ROOT. Files
    | are matched by the authenticated user's DNI; paths are never accepted
    | from the client.
    */
    'enabled' => env('CERTIFICATES_ENABLED', false),
    'disk' => env('CERTIFICATES_DISK', 'certificates'),
    'filename_pattern' => env('CERTIFICATES_FILENAME_PATTERN', '{dni}.pdf'),
];
