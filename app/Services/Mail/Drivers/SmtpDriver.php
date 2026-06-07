<?php

namespace App\Services\Mail\Drivers;

use App\Models\Setting;
use App\Services\Mail\Contracts\EmailDriverInterface;
use Illuminate\Support\Facades\Mail;

class SmtpDriver implements EmailDriverInterface
{
    public function send(string $to, string $subject, string $body): bool
    {
        $host = Setting::get('smtp_host', env('MAIL_HOST', ''));
        $port = Setting::get('smtp_port', env('MAIL_PORT', 25));
        $username = Setting::get('smtp_username', env('MAIL_USERNAME', ''));
        $password = Setting::get('smtp_password', env('MAIL_PASSWORD', ''));
        $fromEmail = Setting::get('smtp_from_email', env('MAIL_FROM_ADDRESS', 'noreply@ambition-cloud.com'));
        $fromName = Setting::get('smtp_from_name', env('MAIL_FROM_NAME', 'SNP Distribution'));
        $encryption = Setting::get('smtp_encryption', env('MAIL_ENCRYPTION', 'tls'));

        if (empty($host)) {
            throw new \RuntimeException(
                'SMTP host is not configured. Set smtp_host in settings or configure MAIL_HOST in .env'
            );
        }

        if (empty($fromEmail)) {
            throw new \RuntimeException(
                'SMTP from email is not configured. Set smtp_from_email in settings or configure MAIL_FROM_ADDRESS in .env'
            );
        }

        config([
            'mail.mailers.dynamic_smtp' => [
                'transport' => 'smtp',
                'host' => $host,
                'port' => (int) $port,
                'username' => $username,
                'password' => $password,
                'encryption' => $encryption ?: null,
                'timeout' => null,
                'local_domain' => null,
            ],
            'mail.from' => [
                'address' => $fromEmail,
                'name' => $fromName,
            ],
        ]);

        Mail::mailer('dynamic_smtp')->raw($body, function ($mail) use ($to, $subject) {
            $mail->to($to)->subject($subject);
        });

        return true;
    }
}
