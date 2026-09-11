<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'firebase' => [
        // Ruta absoluta al JSON de una cuenta de servicio de Firebase. Nunca
        // guardes este archivo dentro del repositorio.
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],

    'robot_call' => [
        'enabled' => env('ROBOT_CALL_ENABLED', false),
        // Ejemplo: https://robot.cenate.pe/api/softphone/robot-call
        'url' => env('ROBOT_CALL_URL'),
        'token' => env('ROBOT_CALL_TOKEN'),
        // Número de prueba o de operación autorizado, configurado solo en VPS.
        'target_number' => env('ROBOT_CALL_TARGET_NUMBER', '953761235'),
        'bridge_enabled' => env('ROBOT_CALL_BRIDGE_ENABLED', false),
        'cloud_url' => rtrim((string) env('ROBOT_CALL_CLOUD_URL', ''), '/'),
        'cloud_token' => env('ROBOT_CALL_CLOUD_TOKEN'),
        'cenate_url' => rtrim((string) env('ROBOT_CALL_CENATE_URL', 'http://10.0.89.237'), '/'),
        'cenate_key' => env('ROBOT_CALL_CENATE_KEY'),
        'test_phone' => env('ROBOT_CALL_TEST_PHONE', '953761235'),
        'bridge_poll_seconds' => (int) env('ROBOT_CALL_BRIDGE_POLL_SECONDS', 10),
        'bridge_lease_seconds' => (int) env('ROBOT_CALL_BRIDGE_LEASE_SECONDS', 120),
        'bridge_max_attempts' => (int) env('ROBOT_CALL_BRIDGE_MAX_ATTEMPTS', 5),
        // Token exclusivo para el bridge local que consulta el VPS.
        'bridge_token' => env('ROBOT_CALL_BRIDGE_TOKEN'),
    ],

];
