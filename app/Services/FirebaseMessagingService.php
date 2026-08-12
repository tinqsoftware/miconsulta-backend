<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebaseMessagingService
{
    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        $credentials = config('services.firebase.credentials');

        if (!$credentials || !is_file($credentials)) {
            Log::warning('No se envió push FCM: FIREBASE_CREDENTIALS no está configurado.');
            return false;
        }

        try {
            $messaging = (new Factory())
                ->withServiceAccount($credentials)
                ->createMessaging();

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData(array_map('strval', $data));

            $messaging->send($message);
            return true;
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar la notificación FCM.', [
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }
}
