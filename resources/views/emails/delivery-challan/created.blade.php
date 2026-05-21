<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery Challan Created</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .header-logo {
            max-height: 50px;
            max-width: 150px;
        }

        .header-right {
            text-align: right;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .company-subtitle {
            font-size: 12px;
            margin: 0;
            opacity: 0.9;
        }

        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }

        .details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 0;
        }

        .detail-label {
            font-weight: bold;
            width: 40%;
            color: #6b7280;
        }

        .detail-value {
            width: 60%;
        }

        .footer {
            background: #1f2937;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 12px;
        }

        .btn {
            display: inline-block;
            background: #11c00b;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 20px;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <img src="{{ config('app.backend_url', 'http://localhost:3000') . '/photo/company_logo.png'}}" alt="Company Logo" class="header-logo">
        </div>
        <div class="header-right">
            <p class="company-name">{{ config('app.name', 'SNP') }}</p>
            <p class="company-subtitle">{{ 'Warranty Service Center' }}</p>
        </div>
    </div>
    <div class="content">
        <p>Dear {{ $customerName }},</p>
        <p>Your product(s) have been delivered successfully. Please find the delivery details below:</p>

        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Delivery Number</span>
                <span class="detail-value"><strong>{{ $deliveryNumber }}</strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Claim(s)</span>
                <span class="detail-value">{{ $claimNumbers }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Delivered At</span>
                <span class="detail-value">{{ $deliveredDateTime }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Remarks</span>
                <span class="detail-value">{{ $deliveredRemarks }}</span>
            </div>
        </div>

        <p>Thank you for your patience and trust in our service. We hope everything is in good condition.</p>
        <p>If you have any questions, please don't hesitate to contact our support team.</p>

        <p>Best regards,<br><strong>{{ config('app.name') }} Team</strong></p>
    </div>
    <div style="text-align: center; margin: 20px 0;">
        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}" class="btn">Visit Our Portal</a>
    </div>
    <div class="footer">
        <p>Need help? Contact us:</p>
        <p>warranty@snpdist.com | 0304-1113767</p>
        <p>Mon–Sat, 11:00 AM – 7:00 PM</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>

</html>
