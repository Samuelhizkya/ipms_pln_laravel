@extends('layouts.app')

@section('title', 'Data KPI & Target KPI')

@section('content')
    

<section
    id="kpi-workspace"
    class="card kpi-workspace"
    data-master-url="{{ route('data.target.master') }}"
data-target-url="{{ route('data.target.rows') }}"
data-unit-url="{{ route('data.target.units') }}"
    data-login-url="{{ route('login') }}"
    aria-busy="true"
>
<div class="kpi-page-actions" data-kpi-actions>
    @if (data_get(session('user', []), 'role') === 'admin')
        <a
    href="{{ route('master-kpi.index') }}"
    class="realisasi-edit-button"
>
    Master KPI
</a>

<a
    href="{{ route('target-kpi.import.form') }}"
    class="kpi-action-import"
>
    <i data-lucide="file-up" aria-hidden="true"></i>
    <span>Impor Target Excel</span>
</a>
    @endif
</div>
    <div class="kpi-toolbar">
        <div class="kpi-field kpi-search-field">
            <label for="kpi-search">Cari KPI</label>
            <input
                id="kpi-search"
                type="search"
                placeholder="Kode atau nama KPI..."
                autocomplete="off"
                disabled
            >
        </div>

        <div class="kpi-field">
            <label for="kpi-unit">Unit Layanan Pelanggan</label>
            <select id="kpi-unit" disabled>
                <option>Memuat unit...</option>
            </select>
        </div>

        <div class="kpi-field">
            <label for="kpi-year">Tahun</label>
            <select id="kpi-year" disabled></select>
        </div>

        <div class="kpi-field">
            <label for="kpi-month">Bulan</label>
            <select id="kpi-month" disabled></select>
        </div>

        <button
            id="kpi-reload"
            class="kpi-page-button"
            type="button"
            disabled
        >
            Muat ulang
        </button>
    </div>

    <p
        id="kpi-message"
        class="kpi-message"
        role="status"
        aria-live="polite"
    >
        Memuat data KPI dan target...
    </p>

    <div
        class="table-wrapper"
        role="region"
        aria-label="Tabel data KPI dan target"
        tabindex="0"
    >
        <table class="kpi-table">
            <thead>
                <tr>
                    <th scope="col">No.</th>
                    <th scope="col">Kode KPI</th>
                    <th scope="col">Nama KPI</th>
                    <th scope="col">Satuan</th>
                    <th scope="col" class="kpi-number">Bobot (%)</th>
                    <th scope="col">Polaritas</th>
                    <th scope="col" class="kpi-number">Target Tahunan</th>
                    <th
                        scope="col"
                        id="kpi-month-heading"
                        class="kpi-number"
                    >
                        Target Bulanan
                    </th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>

            <tbody id="kpi-body">
                <tr>
                    <td colspan="9" class="kpi-empty">Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="kpi-footer">
        <span id="kpi-count">Memuat data...</span>

        <div class="kpi-pagination">
            <label for="kpi-size">Baris</label>

            <select id="kpi-size" disabled>
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
            </select>

            <button
                id="kpi-previous"
                class="kpi-page-button"
                type="button"
                aria-label="Halaman sebelumnya"
                disabled
            >‹</button>

            <span id="kpi-page" aria-live="polite">—</span>

            <button
                id="kpi-next"
                class="kpi-page-button"
                type="button"
                aria-label="Halaman berikutnya"
                disabled
            >›</button>
        </div>
    </div>

    <dialog
        id="kpi-dialog"
        class="kpi-dialog"
        aria-labelledby="kpi-detail-title"
    >
        <div class="kpi-dialog-header">
            <div>
                <h2 id="kpi-detail-title"></h2>
                <p id="kpi-detail-context"></p>
            </div>

            <form method="dialog">
                <button class="kpi-page-button" type="submit">
                    Tutup
                </button>
            </form>
        </div>

        <p id="kpi-detail-annual" class="kpi-detail-annual"></p>

        <table>
            <thead>
                <tr>
                    <th scope="col">Bulan</th>
                    <th scope="col" class="kpi-number">Target</th>
                </tr>
            </thead>
            <tbody id="kpi-detail-body"></tbody>
        </table>

        <p class="kpi-dialog-note">
            Tanda — berarti nilai belum tersedia.
            Nilai ditampilkan sesuai data yang tersimpan.
        </p>
        @if (data_get(session('user', []), 'role') === 'admin')
    <div class="target-quick-edit">
        <button
            id="target-edit-open"
            type="button"
            class="kpi-page-button"
            data-read-template="{{ route('target-kpi.edit-data', ['target' => '__ID__']) }}"
            data-save-template="{{ route('target-kpi.update', ['target' => '__ID__']) }}"
            hidden
        >
            Edit Target
        </button>

        <p id="target-edit-message" role="status" aria-live="polite"></p>

        <form id="target-edit-form" hidden>
            @csrf

            <input type="hidden" name="version">

            <fieldset id="target-edit-fields">
                <legend>Edit nilai target</legend>

                <label for="target-edit-annual">Target Tahunan</label>
                <input
                    id="target-edit-annual"
                    name="target_tahunan"
                    type="number"
                    step="any"
                    required
                >

                <div id="target-edit-months" class="target-edit-grid"></div>

                <p class="target-edit-note">
                    Kolom bulanan kosong berarti target belum tersedia.
                    Angka 0 tetap disimpan sebagai nol.
                    Perubahan juga memperbarui target dan hasil perhitungan
                    realisasi terkait pada ULP, KPI, dan tahun yang sama.
                </p>

                <div class="target-edit-actions">
                    <button type="button" id="target-edit-cancel" class="kpi-page-button">
                        Batal
                    </button>

                    <button type="submit" class="kpi-page-button">
                        Simpan Perubahan
                    </button>
                </div>
            </fieldset>
        </form>
    </div>
@endif
    </dialog>
</section>
@endsection