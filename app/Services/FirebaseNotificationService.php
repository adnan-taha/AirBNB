<?php
namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;

class FirebaseNotificationService
{
    public function __construct(private Messaging $messaging) {}

    public function send(string $token, string $title, string $body, array $data = [])
    {
        if (!$token) {
            throw new \Exception('FCM token is missing');
        }

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        return $this->messaging->send($message);
    }
}
