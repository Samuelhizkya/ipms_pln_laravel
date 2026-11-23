@extends('layouts.app')

@section('title', 'Realisasi KPI')

@section('content')

<section
    id="realisasi-workspace"
    class="card realisasi-workspace"
    data-url="{{ route('data.realisasi-kpi') }}"
    data-unit-url="{{ route('data.target.units') }}"
    data-login-url="{{ route('login') }}"
    data-can-edit="{{ data_get(session('user', []), 'role') === 'ulp' ? 'true' : 'false' }}"
    data-edit-template="{{ route('realisasi-kpi.edit', ['realisasi' => '__ID__']) }}"
    aria-busy="true"
>
    {{-- Tombol tindakan: Impor Excel dan Download --}}
    <div class="kpi-page-actions" data-kpi-actions>
        @if (data_get(session('user', []), 'role') === 'ulp')
            <a
                href="{{ route('realisasi-kpi.import.form') }}"
                class="kpi-action-import"
            >
                <i data-lucide="file-up" aria-hidden="true"></i>
                <span>Impor Excel</span>
            </a>
        @endif
    </div>

    {{-- Pencarian dan filter tabel --}}
    <div class="realisasi-toolbar">
        <div class="realisasi-field realisasi-search-field">
            <label for="realisasi-search">Cari KPI</label>

            <input
                id="realisasi-search"
                type="search"
                placeholder="Kode, nama KPI, atau indikator pendukung..."
                autocomplete="off"
                disabled
            >
        </div>

        <div class="realisasi-field">
            <label for="realisasi-unit">Unit Layanan Pelanggan</label>

            <select id="realisasi-unit" disabled>
                <option value="">Memuat unit...</option>
            </select>
        </div>

        <div class="realisasi-field">
            <label for="realisasi-year">Tahun</label>
            <select id="realisasi-year" disabled></select>
        </div>

        <div class="realisasi-field">
            <label for="realisasi-month">Bulan</label>

            <select id="realisasi-month" disabled>
                <option value="">Semua bulan</option>
            </select>
        </div>

        <div class="realisasi-field">
            <label for="realisasi-status">Status</label>

            <select id="realisasi-status" disabled>
                <option value="">Semua status</option>
                <option value="draft">Draft</option>
                <option value="valid">Valid</option>
                <option value="error">Error</option>
            </select>
        </div>

        <button
            id="realisasi-reload"
            class="realisasi-button"
            type="button"
            disabled
        >
            Muat ulang
        </button>
    </div>

    <p
        id="realisasi-message"
        class="realisasi-message"
        role="status"
        aria-live="polite"
    >
        Memuat realisasi...
    </p>

    <div
        class="realisasi-table-wrapper"
        role="region"
        aria-label="Tabel realisasi KPI"
        tabindex="0"
    >
        <table class="realisasi-table">
            <thead>
                <tr>
                    <th scope="col">No.</th>
                    <th scope="col">Kode KPI</th>
                    <th scope="col">Nama KPI / Indikator Pendukung</th>
                    <th scope="col">Periode</th>
                    <th scope="col" class="realisasi-number">Target Tahunan</th>
                    <th scope="col" class="realisasi-number">Target Periode</th>
                    <th scope="col" class="realisasi-number">Realisasi</th>
                    <th scope="col" class="realisasi-number">Pencapaian (%)</th>
                    <th scope="col" class="realisasi-number">Nilai KPI</th>
                    <th scope="col">Sumber</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>

            <tbody id="realisasi-body">
                <tr>
                    <td colspan="12" class="realisasi-empty">
                        Memuat realisasi...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="realisasi-footer">
        <span id="realisasi-count">Memuat data...</span>

        <div class="realisasi-pagination">
            <label for="realisasi-size">Baris</label>

            <select id="realisasi-size" disabled>
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
            </select>

            <button
                id="realisasi-previous"
                class="realisasi-button"
                type="button"
                aria-label="Halaman sebelumnya"
                disabled
            >‹</button>

            <span id="realisasi-page" aria-live="polite">—</span>

            <button
                id="realisasi-next"
                class="realisasi-button"
                type="button"
                aria-label="Halaman berikutnya"
                disabled
            >›</button>
        </div>
    </div>
</section>
@endsection