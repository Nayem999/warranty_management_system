<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService implements NotificationChannelInterface
{
    protected string $apiUrl;

    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.sms.url', '');
        $this->apiKey = config('services.sms.api_key', '');
    }

    public function send(string $to, string $subject, string $message): bool
    {
        try {
            $response = Http::timeout(30)->post($this->apiUrl, [
                'api_key' => $this->apiKey,
                'to' => $this->formatPhoneNumber($to),
                'message' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('SMS send failed: '.$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('SMS send failed: '.$e->getMessage());

            return false;
        }
    }

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+88')) {
            return $phone;
        }

        if (str_starts_with($phone, '88')) {
            return '+'.$phone;
        }

        if (str_starts_with($phone, '01')) {
            return '+88'.$phone;
        }

        return '+88'.$phone;
    }
}
