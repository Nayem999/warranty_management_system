<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Categories Export</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        th { background: #eee; font-weight: bold; }
        h1 { font-size: 16pt; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Categories Report</h1>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Short Name</th>
                <th>Parent Category</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td>{{ $category->short_name }}</td>
                <td>{{ $category->parent?->name }}</td>
                <td>{{ $category->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
