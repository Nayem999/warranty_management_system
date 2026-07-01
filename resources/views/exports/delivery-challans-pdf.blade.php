<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Challans Export</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        th { background: #eee; font-weight: bold; }
        h1 { font-size: 16pt; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Delivery Challans Report</h1>
    <table>
        <thead>
            <tr>
                <th>Delivery Number</th>
                <th>Customer</th>
                <th>Service Center</th>
                <th>Courier</th>
                <th>Courier Slip</th>
                <th>Delivered Date Time</th>
                <th>Delivered Remarks</th>
                <th>Claim IDs</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryChallans as $challan)
            <tr>
                <td>{{ $challan->delivery_number }}</td>
                <td>{{ $challan->customer?->customer_name }}</td>
                <td>{{ $challan->serviceCenter?->title }}</td>
                <td>{{ $challan->courierOut?->name }}</td>
                <td>{{ $challan->courier_slip_outward }}</td>
                <td>{{ $challan->delivered_date_time }}</td>
                <td>{{ $challan->delivered_remarks }}</td>
                <td>{{ $challan->claim_ids ? implode(', ', $challan->claim_ids) : '' }}</td>
                <td>{{ $challan->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
