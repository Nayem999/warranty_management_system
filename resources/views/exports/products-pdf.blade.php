<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Products Export</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        th { background: #eee; font-weight: bold; }
        h1 { font-size: 16pt; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Products Report</h1>
    <table>
        <thead>
            <tr>
                <th>Model No</th>
                <th>Serial Number</th>
                <th>Item Description</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Sub Category</th>
                <th>Is Countable</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
            <tr>
                <td>{{ $product->model_no }}</td>
                <td>{{ $product->serial_number }}</td>
                <td>{{ $product->item_description }}</td>
                <td>{{ $product->brand?->name }}</td>
                <td>{{ $product->category?->name }}</td>
                <td>{{ $product->subCategory?->name }}</td>
                <td>{{ $product->is_countable ? 'Yes' : 'No' }}</td>
                <td>{{ $product->start_date }}</td>
                <td>{{ $product->end_date }}</td>
                <td>{{ $product->product_status }}</td>
                <td>{{ $product->creator?->first_name }} {{ $product->creator?->last_name }}</td>
                <td>{{ $product->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
