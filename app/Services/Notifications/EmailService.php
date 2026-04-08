<?php

namespace App\Services\Notifications;

use App\Models\EmailLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            Mail::raw($message, function ($mail) use ($to, $subject) {
                $mail->to($to)
                    ->subject($subject);
            });

            $emailLog->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return true;
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
