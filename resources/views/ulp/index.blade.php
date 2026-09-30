<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data ULP - IPMS PLN</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f5f6fa;
        }

        h1 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }
    </style>
</head>

<body>

    <h1>Data ULP</h1>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode ULP</th>
                <th>Nama ULP</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($ulps as $index => $ulp)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $ulp->kode_ulp }}</td>
                    <td>{{ $ulp->nama_ulp }}</td>
                    <td>{{ $ulp->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>