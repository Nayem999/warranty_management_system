<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customers Export</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        th { background: #eee; font-weight: bold; }
        h1 { font-size: 16pt; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Customers Report</h1>
    <table>
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Landline</th>
                <th>Address</th>
                <th>City</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $customer)
            <tr>
                <td>{{ $customer->customer_name }}</td>
                <td>{{ $customer->contact_person }}</td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone }}</td>
                <td>{{ $customer->landline }}</td>
                <td>{{ $customer->address }}</td>
                <td>{{ $customer->city?->name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
