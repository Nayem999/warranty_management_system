<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
<h2>Password Reset Request</h2>
<p>Dear {{ $userName }},</p>
<p>You requested a password reset. Use the OTP below:</p>
<div style="font-size: 24px; font-weight: bold; letter-spacing: 4px; background: #f5f5f5; padding: 10px; text-align: center;">{{ $otp }}</div>
<p>This OTP expires in 60 minutes.</p>
<p>If you did not request this, please ignore this email.</p>
<p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>
