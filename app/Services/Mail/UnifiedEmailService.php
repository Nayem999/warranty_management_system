<?php

namespace App\Services\Mail;

use App\Models\Setting;
use App\Services\Mail\Contracts\EmailDriverInterface;
use App\Services\Mail\Drivers\OutlookDriver;
use App\Services\Mail\Drivers\SmtpDriver;
use Illuminate\Support\Facades\Log;

class UnifiedEmailService
{
    protected ?EmailDriverInterface $driver = null;

    public function driver(): EmailDriverInterface
    {
        if ($this->driver) {
            return $this->driver;
        }

        $driverName = Setting::get('email_driver', 'smtp');

        $this->driver = match ($driverName) {
            'outlook' => new OutlookDriver(),
            default => new SmtpDriver(),
        };

        return $this->driver;
    }

    public static function smtpIsConfigured(): bool
    {
        return !empty(Setting::get('smtp_host', env('MAIL_HOST', '')));
    }

    public function send(string $to, string $subject, string $body, bool $fallbackToSmtp = true): bool
    {
        try {
            return $this->driver()->send($to, $subject, $body);
        } catch (\Exception $e) {
            Log::error('Email driver failed', [
                'driver' => $this->driver ? get_class($this->driver) : 'null',
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            if ($fallbackToSmtp && !($this->driver instanceof SmtpDriver)) {
                if (!static::smtpIsConfigured()) {
                    throw new \RuntimeException(
                        'Outlook email failed and SMTP fallback is not configured. ' .
                        'Authorize Outlook first or set smtp_host in settings. ' .
                        'Original error: ' . $e->getMessage()
                    );
                }

                Log::info('Falling back to SMTP driver', compact('to', 'subject'));

                return (new SmtpDriver())->send($to, $subject, $body);
            }

            throw $e;
        }
    }

    public static function sendUsing(string $to, string $subject, string $body, bool $fallbackToSmtp = true): bool
    {
        return (new static())->send($to, $subject, $body, $fallbackToSmtp);
    }
}
