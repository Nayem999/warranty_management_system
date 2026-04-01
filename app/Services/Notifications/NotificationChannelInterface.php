<?php

namespace App\Services\Notifications;

interface NotificationChannelInterface
{
    public function send(string $to, string $subject, string $message): bool;
}
