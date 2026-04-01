<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements NotificationChannelInterface
{
    protected string $apiUrl;

    protected string $apiKey;

    protected string $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.url', '');
        $this->apiKey = config('services.whatsapp.api_key', '');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
    }

    public function send(string $to, string $subject, string $message): bool
    {
        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->post($this->apiUrl, [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('WhatsApp send failed: '.$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed: '.$e->getMessage());

            return false;
        }
    }

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+')) {
            return ltrim($phone, '+');
        }

        if (str_starts_with($phone, '88')) {
            return ltrim($phone, '8');
        }

        if (str_starts_with($phone, '01')) {
            return '88'.$phone;
        }

        return $phone;
    }
}
