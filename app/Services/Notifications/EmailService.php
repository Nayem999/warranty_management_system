<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Mail;

class EmailService implements NotificationChannelInterface
{
    public function send(string $to, string $subject, string $message): bool
    {
        try {
            Mail::raw($message, function ($mail) use ($to, $subject) {
                $mail->to($to)
                    ->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Email send failed: '.$e->getMessage());

            return false;
        }
    }
}
