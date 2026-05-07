<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Claims Export</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        th { background: #eee; font-weight: bold; }
        h1 { font-size: 16pt; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Claims Report</h1>
    <table>
        <thead>
            <tr>
                <th>Claim Number</th>
                <th>Status</th>
                <th>Claim Date</th>
                <th>Customer Name</th>
                <th>Customer Email</th>
                <th>Customer Phone</th>
                <th>Customer City</th>
                <th>Product Serial</th>
                <th>Product Name</th>
                <th>Brand</th>
                <th>Service Center</th>
                <th>Problem Description</th>
                <th>Created By</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($claims as $claim)
            <tr>
                <td>{{ $claim->claim_number }}</td>
                <td>{{ $claim->status }}</td>
                <td>{{ $claim->claim_date }}</td>
                <td>{{ $claim->customer?->customer_name }}</td>
                <td>{{ $claim->customer?->email }}</td>
                <td>{{ $claim->customer?->phone }}</td>
                <td>{{ $claim->customer?->city }}</td>
                <td>{{ $claim->product?->product_serial }}</td>
                <td>{{ $claim->product?->product_name }}</td>
                <td>{{ $claim->product?->brand?->name }}</td>
                <td>{{ $claim->serviceCenter?->title }}</td>
                <td>{{ $claim->problem_description }}</td>
                <td>{{ $claim->creator?->first_name }} {{ $claim->creator?->last_name }}</td>
                <td>{{ $claim->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
