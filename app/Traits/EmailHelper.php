<?php

namespace App\Traits;

use App\Models\EmailLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait EmailHelper
{
    public function sendEmail($mailable, string $to, string $subject = ''): bool
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
            Mail::to($to)->send($mailable);

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
