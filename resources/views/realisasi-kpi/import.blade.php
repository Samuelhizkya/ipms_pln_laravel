<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Import Realisasi KPI - IPMS PLN</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 40px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 32px;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 30px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        select,
        input[type="file"] {
            width: 100%;
            padding: 11px;
            margin-bottom: 22px;
            box-sizing: border-box;
        }

        button {
            background: #0878d1;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #0565b3;
        }

        .info {
            background: #e8f5ff;
            color: #075985;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .success {
            background: #e8f7ed;
            color: #166534;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .error {
            background: #feecec;
            color: #991b1b;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .result {
            margin-top: 30px;
        }

        .result h2 {
            margin-bottom: 15px;
        }

        .summary {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-box {
            flex: 1;
            padding: 18px;
            border-radius: 10px;
            background: #f4f7fb;
        }

        .summary-box strong {
            display: block;
            font-size: 25px;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f4f7fb;
        }

        .small {
            color: #666;
            font-size: 13px;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Import Realisasi KPI</h1>

    <div class="info">
        Upload file Excel Realisasi KPI sesuai template yang digunakan PLN.
    </div>


    {{-- ========================================================= --}}
    {{-- PESAN ERROR VALIDASI FORM --}}
    {{-- ========================================================= --}}

    @if ($errors->any())

        <div class="error">

            <strong>Terdapat kesalahan:</strong>

            <ul>

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- PESAN BERHASIL --}}
    {{-- ========================================================= --}}

    @if (session('success'))

        <div class="success">

            {{ session('success') }}

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- ERROR DARI IMPORT EXCEL --}}
    {{-- ========================================================= --}}

    @if (session('errors_import'))

        <div class="error">

            <strong>
                Beberapa baris tidak dapat diproses:
            </strong>

            <ul>

                @foreach (session('errors_import') as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- FORM IMPORT --}}
    {{-- ========================================================= --}}

    <form
        method="POST"
        action="{{ route('realisasi-kpi.import') }}"
        enctype="multipart/form-data"
    >

        @csrf


        {{-- ULP --}}

        <label for="id_ulp">
            ULP
        </label>

        <select
            name="id_ulp"
            id="id_ulp"
            required
        >

            <option value="">
                -- Pilih ULP --
            </option>

            @foreach ($ulps as $ulp)

                <option
                    value="{{ $ulp->id_ulp }}"
                    {{ old('id_ulp') == $ulp->id_ulp ? 'selected' : '' }}
                >

                    {{ $ulp->nama_ulp }}

                </option>

            @endforeach

        </select>


        {{-- PERIODE --}}

        <label for="id_periode">
            Periode
        </label>

        <select
            name="id_periode"
            id="id_periode"
            required
        >

            <option value="">
                -- Pilih Periode --
            </option>

            @foreach ($periodes as $periode)

                <option
                    value="{{ $periode->id_periode }}"
                    {{ old('id_periode') == $periode->id_periode ? 'selected' : '' }}
                >

                    {{ \Carbon\Carbon::createFromDate(
                        $periode->tahun,
                        $periode->bulan,
                        1
                    )->translatedFormat('F Y') }}

                </option>

            @endforeach

        </select>


        {{-- FILE --}}

        <label for="file">
            File Excel Realisasi KPI
        </label>

        <input
            type="file"
            name="file"
            id="file"
            accept=".xlsx,.xls"
            required
        >


        {{-- BUTTON --}}

        <button type="submit">
            Upload & Import
        </button>

    </form>


    {{-- ========================================================= --}}
    {{-- HASIL PEMBACAAN EXCEL --}}
    {{-- ========================================================= --}}

    @if (session('valid_rows'))

        @php
            $validRows = session('valid_rows');
        @endphp

        <div class="result">

            <h2>
                Hasil Pembacaan Excel
            </h2>


            <div class="summary">

                <div class="summary-box">

                    Baris valid

                    <strong>
                        {{ count($validRows) }}
                    </strong>

                </div>


                <div class="summary-box">

                    Baris bermasalah

                    <strong>
                        {{ count(session('errors_import', [])) }}
                    </strong>

                </div>

            </div>


            @if (count($validRows) > 0)

                <table>

                    <thead>

                        <tr>

                            <th>
                                No
                            </th>

                            <th>
                                Indikator
                            </th>

                            <th>
                                Pendukung
                            </th>

                            <th>
                                Satuan
                            </th>

                            <th>
                                Realisasi
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($validRows as $index => $row)

                            <tr>

                                <td>
                                    {{ $index + 1 }}
                                </td>

                                <td>
                                    {{ $row['indikator'] }}
                                </td>

                                <td>
                                    {{ $row['nama_pendukung'] ?? '-' }}
                                </td>

                                <td>
                                    {{ $row['satuan_pendukung'] ?? '-' }}
                                </td>

                                <td>
                                    {{ $row['realisasi'] ?? '-' }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            @else

                <p class="small">
                    Tidak ada baris yang berhasil divalidasi.
                </p>

            @endif

        </div>

    @endif

</div>

</body>

</html>