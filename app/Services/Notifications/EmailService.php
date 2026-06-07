<?php

namespace App\Services\Notifications;

use App\Models\EmailLog;
use App\Services\Mail\UnifiedEmailService;
use Illuminate\Support\Facades\Log;

class EmailService implements NotificationChannelInterface
{
    public function send(string $to, string $subject, string $message): bool
    {
        $emailLog = EmailLog::create([
            'to_email' => $to,
            'subject' => $subject,
            'status' => 'pending',
        ]);

        if (! config('app.email_status', false)) {
            $emailLog->update([
                'status' => 'skipped',
                'reason' => 'EMAIL_STATUS is false',
            ]);

            return false;
        }

        try {
            $result = (new UnifiedEmailService())->send($to, $subject, $message);

            if ($result) {
                $emailLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } else {
                $emailLog->update([
                    'status' => 'failed',
                    'reason' => 'Email driver returned false',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $emailLog->update([
                'status' => 'failed',
                'reason' => $e->getMessage(),
            ]);

            Log::error('Email send failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
