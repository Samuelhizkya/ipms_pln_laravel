@extends('layouts.app')

@section('title', 'Edit Realisasi KPI')
@section('navbar-title', 'Edit Realisasi KPI')

@section('content')
@php
    $months = [
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

    $backUrl = route('realisasi.kpi', [
        'id_ulp' => $record->id_ulp,
        'id_periode' => $record->id_periode,
        'tahun' => $record->periode?->tahun,
        'bulan' => $record->periode?->bulan,
    ]);

    $monthName = $months[(int) $record->periode?->bulan] ?? '—';
@endphp

<section class="card realisasi-edit-card">
    <div class="realisasi-edit-heading">
        <div>
            <h2>Edit Realisasi KPI</h2>
            <p>Perbarui nilai realisasi untuk periode yang tercantum.</p>
        </div>

        <a href="{{ $backUrl }}" class="realisasi-edit-button">
            Kembali
        </a>
    </div>

    <dl class="realisasi-edit-context">
        <div>
            <dt>ULP</dt>
            <dd>{{ $record->ulp?->nama_ulp ?? '—' }}</dd>
        </div>

        <div>
            <dt>Periode</dt>
            <dd>
                {{ $monthName }}
                {{ $record->periode?->tahun }}
            </dd>
        </div>

        <div>
            <dt>KPI</dt>
            <dd>
                {{ $record->kpi?->kode_kpi ?? '—' }}
                ·
                {{ $record->kpi?->nama_kpi ?? '—' }}
            </dd>
        </div>

        @if ($record->nama_pendukung)
            <div>
                <dt>Indikator pendukung</dt>
                <dd>{{ $record->nama_pendukung }}</dd>
            </div>
        @endif

        <div>
            <dt>Satuan</dt>
            <dd>
                {{ $record->satuan_pendukung ?: ($record->kpi?->satuan ?? '—') }}
            </dd>
        </div>
    </dl>

    <form
        method="POST"
        action="{{ route('realisasi-kpi.update', [
            'realisasi' => $record->id_realisasi,
        ]) }}"
    >
        @csrf
        @method('PUT')

        <input
            type="hidden"
            name="version"
            value="{{ old('version', $version) }}"
        >

        @error('version')
            <div class="realisasi-edit-conflict" role="alert">
                <p>{{ $message }}</p>

                <a href="{{ route('realisasi-kpi.edit', [
                    'realisasi' => $record->id_realisasi,
                ]) }}">
                    Muat ulang data
                </a>
            </div>
        @enderror

        <div class="realisasi-field">
            <label for="edit-realisasi-value">Nilai realisasi</label>

            <input
                id="edit-realisasi-value"
                name="realisasi"
                type="number"
                step="any"
                value="{{ old('realisasi', $record->realisasi) }}"
                required
                aria-describedby="edit-realisasi-help"
                @error('realisasi') aria-invalid="true" @enderror
            >

            @error('realisasi')
                <p class="realisasi-edit-error" role="alert">
                    {{ $message }}
                </p>
            @enderror

            <small id="edit-realisasi-help">
                Gunakan skala angka yang sama dengan data impor sebelumnya.
                Angka 0 diperbolehkan; kolom tidak boleh kosong.
            </small>
        </div>

        <p class="realisasi-edit-note">
            Pencapaian dan nilai KPI dihitung otomatis oleh sistem.
            Jika target belum tersedia, hasil perhitungan tetap kosong.
        </p>

        <div class="realisasi-edit-actions">
            <a href="{{ $backUrl }}" class="realisasi-edit-button">
                Batal
            </a>

            <button
                type="submit"
                class="realisasi-edit-button is-primary"
                @disabled($errors->has('version'))
            >
                Simpan Perubahan
            </button>
        </div>
    </form>
</section>
@endsection