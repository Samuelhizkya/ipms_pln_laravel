<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Import Target KPI - IPMS PLN</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 40px;
        }

        .container {
            max-width: 800px;
            margin: auto;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        select,
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        button {
            background: #0066cc;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
        }

        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error ul {
            margin-bottom: 0;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card">

        <h1>Import Target KPI</h1>

        @if(session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('import_errors'))
            <div class="error">
                <strong>Import gagal karena terdapat kesalahan:</strong>

                <ul>
                    @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($errors->any())
            <div class="error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('target-kpi.import') }}"
            method="POST"
            enctype="multipart/form-data"
        >

            @csrf

            <div class="form-group">
                <label for="id_ulp">ULP</label>

                <select name="id_ulp" id="id_ulp" required>

                    <option value="">-- Pilih ULP --</option>

                    @foreach($ulps as $ulp)

                        <option
                            value="{{ $ulp->id_ulp }}"
                            {{ old('id_ulp') == $ulp->id_ulp ? 'selected' : '' }}
                        >
                            {{ $ulp->nama_ulp }}
                        </option>

                    @endforeach

                </select>
            </div>

            <div class="form-group">

                <label for="tahun">Tahun</label>

                <input
                    type="number"
                    name="tahun"
                    id="tahun"
                    value="{{ old('tahun', 2026) }}"
                    min="2000"
                    max="2100"
                    required
                >

            </div>

            <div class="form-group">

                <label for="file">File Excel Target KPI</label>

                <input
                    type="file"
                    name="file"
                    id="file"
                    accept=".xlsx,.xls,.csv"
                    required
                >

            </div>

            <button type="submit">
                Upload & Import
            </button>

        </form>

    </div>

</div>

</body>
</html>