<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Work Order Status Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #7c3aed; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .status-update { background: white; padding: 25px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .old-status { color: #9ca3af; text-decoration: line-through; }
        .new-status { color: #059669; font-size: 24px; font-weight: bold; }
        .arrow { color: #6b7280; font-size: 20px; margin: 10px 0; }
        .details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; border-bottom: 1px solid #e5e7eb; padding: 10px 0; }
        .detail-label { font-weight: bold; width: 40%; color: #6b7280; }
        .detail-value { width: 60%; }
        .status-message { background: #eff6ff; padding: 15px; border-radius: 8px; border-left: 4px solid #2563eb; margin: 20px 0; }
        .footer { background: #1f2937; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Work Order Status Update</h1>
    </div>
    <div class="content">
        <p>Dear {{ $customerName }},</p>
        <p>Your work order status has been updated. Here are the details:</p>
        
        <div class="status-update">
            <div class="old-status">{{ $previousStatus }}</div>
            <div class="arrow">↓</div>
            <div class="new-status">{{ $currentStatus }}</div>
        </div>

        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Work Order Number</span>
                <span class="detail-value"><strong>{{ $workOrderNumber }}</strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Product</span>
                <span class="detail-value">{{ $productName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Updated Date</span>
                <span class="detail-value">{{ $updatedDate }}</span>
            </div>
        </div>

        <div class="status-message">
            <strong>What's Next:</strong><br>
            {{ $statusMessage }}
        </div>

        <p>If you have any questions or concerns, please contact our support team.</p>
        
        <p>Thank you for choosing {{ config('app.name') }}.</p>
        
        <p>Best regards,<br><strong>{{ config('app.name') }} Team</strong></p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
