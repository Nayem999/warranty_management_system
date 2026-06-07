<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cities Export</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        th { background: #eee; font-weight: bold; }
        h1 { font-size: 16pt; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Cities Report</h1>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cities as $city)
            <tr>
                <td>{{ $city->name }}</td>
                <td>{{ $city->code }}</td>
                <td>{{ $city->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
