<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Work Order Created</title>
</head>

<body style="margin:0; padding:0; background:#f3f4f6; font-family: Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:20px;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:8px; overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background:#2563eb; padding:15px;">
                            <table width="100%">
                                <tr>
                                    <td align="left">
                                        <img src="{{ config('app.backend_url', 'http://localhost:3000') . '/photo/company_logo.png' }}"
                                            style="max-height:50px;">
                                    </td>
                                    <td align="right" style="color:#fff;">
                                        <div style="font-size:18px; font-weight:bold;">
                                            {{ config('app.name', 'SNP') }}
                                        </div>
                                        <div style="font-size:12px;">
                                            Warranty Service Center
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:25px; background:#f9fafb;">
                            <p>Dear {{ $customerName }},</p>
                            <p>Your claim is currently being processed by our service team.</p>

                            <!-- Details Box -->
                            <table width="100%" cellpadding="10" cellspacing="0"
                                style="background:#ffffff; border:1px solid #e5e7eb; border-radius:6px; margin-top:15px;">

                                <tr>
                                    <td colspan="2" style="font-weight:bold;">Claim Details</td>
                                </tr>

                                <tr>
                                    <td style="width:40%; color:#6b7280;">Status</td>
                                    <td>
                                        <span
                                            style="background:#16a34a; color:#fff; padding:5px 10px; border-radius:12px; font-size:12px;">
                                            {{ $workOrderStatus }}
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="color:#6b7280;">Claim Number</td>
                                    <td>{{ $claimNumber }}</td>
                                </tr>

                                <tr>
                                    <td style="color:#6b7280;">Claim Date</td>
                                    <td>{{ $createdDate }}</td>
                                </tr>

                                <tr>
                                    <td style="color:#6b7280;">Product</td>
                                    <td>{{ $productName }}</td>
                                </tr>

                                <tr>
                                    <td style="color:#6b7280;">Serial Number</td>
                                    <td>{{ $productSerial }}</td>
                                </tr>

                            </table>

                            <!-- Info Card -->
                            <table width="100%" cellpadding="15" cellspacing="0"
                                style="margin-top:15px; border-left:4px solid #28a745; background:#ffffff;">
                                <tr>
                                    <td>
                                        <div style="font-size:12px; text-transform:uppercase; color:#28a745;">
                                            In Progress!
                                        </div>
                                        <div style="font-size:13px; margin-top:5px;">
                                            Our technicians are actively working on your claim.
                                            Estimated resolution time: <strong>3–5 business days</strong>.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Button -->
                            <div style="text-align:center; margin-top:20px;">
                                <a href="{{ config('app.frontend_url', 'http://localhost:3000') . '/track?claimNumber=' . $claimNumber }}"
                                    style="background:#16a34a; color:#fff; padding:12px 20px; text-decoration:none; border-radius:20px; display:inline-block;">
                                    Track Your Claim
                                </a>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#1f2937; color:#ffffff; text-align:center; padding:20px; font-size:12px;">
                            <p>Need help? Contact us:</p>
                            <p>warranty@snpdist.com | 0304-1113767</p>
                            <p>Mon–Sat, 11:00 AM – 7:00 PM</p>
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
