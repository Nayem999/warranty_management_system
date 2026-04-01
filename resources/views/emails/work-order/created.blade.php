<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Work Order Created</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; border-bottom: 1px solid #e5e7eb; padding: 10px 0; }
        .detail-label { font-weight: bold; width: 40%; color: #6b7280; }
        .detail-value { width: 60%; }
        .status-badge { display: inline-block; background: #059669; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .footer { background: #1f2937; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Work Order Created</h1>
    </div>
    <div class="content">
        <p>Dear {{ $customerName }},</p>
        <p>Your work order has been created and is now being processed. Here are the details:</p>
        
        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Work Order Number</span>
                <span class="detail-value"><strong>{{ $workOrderNumber }}</strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Claim Number</span>
                <span class="detail-value">{{ $claimNumber }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Product</span>
                <span class="detail-value">{{ $productName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="status-badge">{{ $status }}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Created Date</span>
                <span class="detail-value">{{ $createdDate }}</span>
            </div>
        </div>

        <p>Our service team will contact you soon to schedule the service or collect your product.</p>
        
        <p>You can track the progress of your work order using our online portal.</p>
        
        <p>Thank you for your patience.</p>
        
        <p>Best regards,<br><strong>{{ config('app.name') }} Team</strong></p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
