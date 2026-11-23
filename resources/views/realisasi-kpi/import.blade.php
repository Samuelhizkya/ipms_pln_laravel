@extends('layouts.app')

@section('title', 'Impor Realisasi KPI')
@section('navbar-title', 'Impor Realisasi KPI')

@section('content')
@php
    $accountUnit = request()->attributes->get('account_ulp');

    $monthNames = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

@endphp

<section class="card realisasi-upload">
    <div class="realisasi-upload-heading">
        <div>
            <h2>Import Realisasi KPI</h2>
            <p>
                Unggah realisasi untuk ULP akun yang sedang digunakan.
            </p>
        </div>

        <a
            href="{{ route('realisasi.kpi') }}"
            class="realisasi-button"
        >
            Kembali ke tabel
        </a>
    </div>


    @if (!$accountUnit)
        <div class="realisasi-upload-alert is-error" role="alert">
            Identitas ULP tidak tersedia. Silakan login kembali.
        </div>
    @else
        <form
            id="realisasi-upload-form"
            method="POST"
            action="{{ route('realisasi-kpi.import') }}"
            enctype="multipart/form-data"
        >
            @csrf

            {{-- id_ulp ditetapkan middleware, bukan dipilih melalui form. --}}

            <div class="realisasi-upload-grid">
                <div class="realisasi-field">
                    <label for="upload-unit">Unit Layanan Pelanggan</label>

                    <input
                        id="upload-unit"
                        type="text"
                        value="{{ $accountUnit->nama_ulp }}"
                        readonly
                    >

                    <small>
                        Data akan disimpan untuk ULP ini.
                    </small>
                </div>

                <div class="realisasi-field">
                    <label for="upload-period">Periode Realisasi</label>

                    <select
                        id="upload-period"
                        name="id_periode"
                        required
                        @disabled($periodes->isEmpty())
                    >
                        <option value="">Pilih periode</option>

                        @foreach ($periodes as $periode)
                            <option
                                value="{{ $periode->id_periode }}"
                                @selected(
                                    (string) old('id_periode') ===
                                    (string) $periode->id_periode
                                )
                            >
                                {{ $monthNames[(int) $periode->bulan] ?? $periode->bulan }}
                                {{ $periode->tahun }}
                            </option>
                        @endforeach
                    </select>

                    @error('id_periode')
    <small role="alert" style="color: #b91c1c;">
        {{ $message }}
    </small>
@enderror

@if ($periodes->isEmpty())
    <small>
        Periode belum tersedia. Hubungi Admin untuk
        berkoordinasi dengan pengelola data.
    </small>
@else
    <small>
        Pilih bulan dan tahun sesuai isi file Excel.
        Pilihan periode mengikuti data yang tersedia di sistem.
    </small>
@endif
                </div>
            </div>

            <div class="realisasi-field realisasi-upload-file">
                <label for="upload-file">File Excel Realisasi</label>

                <input
                    id="upload-file"
                    name="file"
                    type="file"
                    accept=".xlsx,.xls"
                    aria-describedby="upload-file-help"
                    required
                >

                <small id="upload-file-help">
                    Format .xlsx atau .xls, maksimal 10 MB.
                    Gunakan template realisasi yang sesuai.
                </small>
            </div>

            <div class="realisasi-upload-note">
                <p>
                    Pastikan isi file merupakan realisasi
                    <strong>{{ $accountUnit->nama_ulp }}</strong>
                    untuk periode yang dipilih.
                </p>

                <p>
                    Jika periode sudah memiliki data realisasi,
                    sistem akan menolak impor ulang.
                </p>
            </div>

            <p
                id="upload-client-message"
                class="realisasi-upload-client-message"
                role="status"
                aria-live="polite"
            ></p>

            <div class="realisasi-upload-actions">
                <a
                    href="{{ route('realisasi.kpi') }}"
                    class="realisasi-button"
                >
                    Batal
                </a>

                <button
                    id="upload-submit"
                    type="submit"
                    class="realisasi-button realisasi-upload-submit"
                    @disabled($periodes->isEmpty())
                >
                    Import dan Simpan
                </button>
            </div>
        </form>
    @endif
</section>
@endsection