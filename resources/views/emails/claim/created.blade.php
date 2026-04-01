<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Claim Created</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; border-bottom: 1px solid #e5e7eb; padding: 10px 0; }
        .detail-label { font-weight: bold; width: 40%; color: #6b7280; }
        .detail-value { width: 60%; }
        .footer { background: #1f2937; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Claim Created Successfully</h1>
    </div>
    <div class="content">
        <p>Dear {{ $customerName }},</p>
        <p>Your claim has been created successfully. Here are the details:</p>
        
        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Claim Number</span>
                <span class="detail-value"><strong>{{ $claimNumber }}</strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Product</span>
                <span class="detail-value">{{ $productName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Serial Number</span>
                <span class="detail-value">{{ $productSerial }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Issue Description</span>
                <span class="detail-value">{{ $problemDescription }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Claim Date</span>
                <span class="detail-value">{{ $claimDate }}</span>
            </div>
        </div>

        <p>We will keep you updated on the progress of your claim. Our team is working diligently to resolve your issue.</p>
        
        <p>If you have any questions, please don't hesitate to contact our support team.</p>
        
        <p>Best regards,<br><strong>{{ config('app.name') }} Team</strong></p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
