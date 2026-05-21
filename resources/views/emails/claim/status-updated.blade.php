<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Claim Status Update</title>
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

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 14px;
        }

        .status-previous {
            background: #fef3c7;
            color: #92400e;
        }

        .status-current {
            background: #d1fae5;
            color: #065f46;
        }

        .footer {
            background: #1f2937;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 12px;
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
        <p>Your claim status has been updated. Here are the latest details:</p>

        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Claim Number</span>
                <span class="detail-value"><strong>{{ $claimNumber }}</strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Previous Status</span>
                <span class="detail-value"><span class="status-badge status-previous">{{ $previousStatus }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Current Status</span>
                <span class="detail-value"><span class="status-badge status-current">{{ $currentStatus }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Product</span>
                <span class="detail-value">{{ $productName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Serial Number</span>
                <span class="detail-value">{{ $productSerial }}</span>
            </div>
        </div>

        <p>We will keep you updated on the progress of your claim. If you have any questions, please contact our support team.</p>

        <p>Best regards,<br><strong>{{ config('app.name') }} Team</strong></p>
    </div>
    <div class="footer">
        <p>Need help? Contact us:</p>
        <p>warranty@snpdist.com | 0304-1113767</p>
        <p>Mon–Sat, 11:00 AM – 7:00 PM</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>

</html>
