@extends('layouts.app')

@section('title', 'Laporan Kinerja')

@section('content')



<div
    class="lp-page"
    id="lpPage"
    data-api-base="{{ url('/data/monitoring') }}"
>

    <div class="lp-print-heading">
        <h1>IPMS PLN UP3 Palu | Laporan Kinerja</h1>
        <p id="lpPrintDescription"></p>
    </div>

    <section class="lp-panel lp-filterpanel">
        <div class="lp-head">
            <h2 class="lp-title">
                <i data-lucide="sliders-horizontal"></i>
                Filter Laporan Kinerja
            </h2>
        </div>

        <div class="lp-fields">
            <div class="lp-field">
                <label for="lpUlp">Pilih ULP</label>
                <select id="lpUlp">
                    <option value="all">Seluruh ULP</option>
                </select>
            </div>

            <div class="lp-field">
                <label for="lpYear">Tahun</label>
                <select id="lpYear">
                    <option value="2026" selected>2026</option>
                </select>
            </div>

            <div class="lp-field">
                <label for="lpMonth">Bulan / Periode</label>
                <select id="lpMonth">
                    <option value="all">Seluruh Tahun</option>
                </select>
            </div>

            <div class="lp-field">
                <label for="lpStatus">Status KPI</label>
                <select id="lpStatus">
                    <option value="all">Semua Status</option>
                    <option value="ok">Tercapai</option>
                    <option value="warn">Waspada</option>
                    <option value="bad">Belum Tercapai</option>
                    <option value="off">Belum dinilai</option>
                </select>
            </div>
        </div>

        <div class="lp-filterfoot">
            <p class="lp-active" id="lpActive" aria-live="polite">
                Memuat data laporan...
            </p>

            <div class="lp-actions">
                <button class="lp-btn secondary" id="lpReset" type="button">
                    Reset
                </button>

                <button class="lp-btn" id="lpApply" type="button">
                    <i data-lucide="search"></i>
                    Tampilkan Laporan
                </button>
            </div>
        </div>
    </section>

    <div class="lp-summary">
        <div class="lp-card">
            <div class="lp-icon">
                <i data-lucide="building-2"></i>
            </div>
            <small>ULP dalam Laporan</small>
            <strong id="lpUnits">--</strong>
            <span>Unit yang memiliki entri pada hasil filter</span>
        </div>

        <div class="lp-card">
            <div class="lp-icon">
                <i data-lucide="clipboard-list"></i>
            </div>
            <small>Entri KPI</small>
            <strong id="lpEntries">--</strong>
            <span>Jumlah baris data kinerja yang ditampilkan</span>
        </div>

        <div class="lp-card">
            <div class="lp-icon">
                <i data-lucide="circle-check"></i>
            </div>
            <small>KPI Tercapai</small>
            <strong id="lpAchieved">--</strong>
            <span>Memenuhi target pada hasil filter</span>
        </div>

        <div class="lp-card">
            <div class="lp-icon">
                <i data-lucide="percent"></i>
            </div>
            <small>Rata-rata Pencapaian</small>
            <strong id="lpAverage">--</strong>
            <span>Rerata rasio pencapaian KPI, bukan NKO</span>
        </div>
    </div>

    <section class="lp-panel">
        <div class="lp-identity">
            <div>
                <h3>Laporan Hasil Kinerja ULP</h3>
                <p id="lpSubtitle">
                    Rekapitulasi target, realisasi, dan pencapaian
                    kinerja berdasarkan data tersimpan.
                </p>
            </div>

            <span class="lp-identity-mark">
                DATA DATABASE · STATUS KPI SEMENTARA
            </span>
        </div>

        <div class="lp-detailbar">
            <div class="lp-dtile">
                <small>Periode Laporan</small>
                <strong id="lpReportPeriod">--</strong>
            </div>

            <div class="lp-dtile">
                <small>Wilayah / ULP</small>
                <strong id="lpReportUlp">--</strong>
            </div>

            <div class="lp-dtile">
                <small>Nilai Kinerja Keseluruhan / NKO</small>
                <strong id="lpNko">--</strong>
            </div>

            <div class="lp-dtile">
                <small>KPI Perlu Perhatian</small>
                <strong id="lpAttention">--</strong>
            </div>
        </div>

        <div class="lp-alert">
            Target memakai nilai periode, realisasi dan pencapaian
            mengikuti data tersimpan tanpa dihitung ulang.
            Tanda — berarti nilai belum tersedia, bukan nol.
            NKO dan peringkat resmi belum tersedia.
            Jumlah entri termasuk pendukung KPI, bukan jumlah KPI unik.
            Kategori sementara: tercapai ≥100%, waspada ≥90% dan
            &lt;100%, selebihnya belum tercapai; perlu disepakati tim.
        </div>
    </section>

    <section class="lp-panel" id="lpTablePanel">
        <div class="lp-tablehead">
            <h2 class="lp-title">
                <i data-lucide="table-2"></i>
                Rekapitulasi Target, Realisasi, dan Hasil Kinerja
            </h2>

            <input
                type="search"
                class="lp-search"
                id="lpSearch"
                aria-label="Cari KPI atau ULP"
                placeholder="Cari nama KPI atau ULP..."
            >
        </div>

        <div class="lp-tablewrap">
            <table class="lp-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>ULP</th>
                        <th>Periode</th>
                        <th>Kode / Indikator KPI</th>
                        <th>Satuan</th>
                        <th>Target</th>
                        <th>Realisasi</th>
                        <th>Pencapaian</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="lpTableBody"></tbody>
            </table>
        </div>

        <table class="lp-print-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>ULP</th>
                    <th>Periode</th>
                    <th>Kode / Indikator</th>
                    <th>Satuan</th>
                    <th>Target</th>
                    <th>Realisasi</th>
                    <th>Pencapaian</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="lpPrintBody"></tbody>
        </table>

        <div class="lp-pager">
            <span id="lpRowCount"></span>

            <div class="lp-pagerbuttons">
                <button type="button" id="lpPrev">Sebelumnya</button>
                <span id="lpPageNumber"></span>
                <button type="button" id="lpNext">Berikutnya</button>
            </div>
        </div>
    </section>

    <div class="lp-bottomgrid">
        <section class="lp-panel">
            <div class="lp-head">
                <h2 class="lp-title">
                    <i data-lucide="chart-bar-big"></i>
                    Ringkasan Status Laporan
                </h2>
            </div>

            <div class="lp-breakdown">
                <div class="lp-breakrow">
                    <span class="lp-label">
                        <i class="lp-dot" style="background:#237A34"></i>
                        Tercapai
                    </span>
                    <div class="lp-track">
                        <div
                            id="lpGreenBar"
                            class="lp-fill"
                            style="background:#237A34;width:0"
                        ></div>
                    </div>
                    <span class="lp-count" id="lpGreenCount">0</span>
                </div>

                <div class="lp-breakrow">
                    <span class="lp-label">
                        <i class="lp-dot" style="background:#F39C12"></i>
                        Waspada
                    </span>
                    <div class="lp-track">
                        <div
                            id="lpYellowBar"
                            class="lp-fill"
                            style="background:#F39C12;width:0"
                        ></div>
                    </div>
                    <span class="lp-count" id="lpYellowCount">0</span>
                </div>

                <div class="lp-breakrow">
                    <span class="lp-label">
                        <i class="lp-dot" style="background:#E03E3E"></i>
                        Belum Tercapai
                    </span>
                    <div class="lp-track">
                        <div
                            id="lpRedBar"
                            class="lp-fill"
                            style="background:#E03E3E;width:0"
                        ></div>
                    </div>
                    <span class="lp-count" id="lpRedCount">0</span>
                </div>

                <div class="lp-breakrow">
                    <span class="lp-label">
                        <i class="lp-dot" style="background:#9AA9B5"></i>
                        Belum dinilai
                    </span>
                    <div class="lp-track">
                        <div
                            id="lpOffBar"
                            class="lp-fill"
                            style="background:#9AA9B5;width:0"
                        ></div>
                    </div>
                    <span class="lp-count" id="lpOffCount">0</span>
                </div>
            </div>
        </section>

        <section class="lp-panel">
            <div class="lp-head">
                <h2 class="lp-title">
                    <i data-lucide="download"></i>
                    Cetak dan Ekspor Laporan
                </h2>
            </div>

            <div class="lp-explain">
                Laporan mengikuti
                <strong>ULP, periode, status, dan pencarian</strong>
                yang sedang dipilih. Unduh data dalam format CSV yang
                bisa dibuka di Microsoft Excel, atau cetak dan simpan
                sebagai PDF melalui dialog cetak browser.
            </div>

            <div class="lp-actions" style="margin-top:17px">
                <button class="lp-btn" type="button" id="lpExport">
                    <i data-lucide="file-spreadsheet"></i>
                    Ekspor ke Excel (CSV)
                </button>

                <button class="lp-btn secondary" type="button" id="lpPrint">
                    <i data-lucide="printer"></i>
                    Cetak / Simpan PDF
                </button>
            </div>

            <p class="lp-note">
                CSV bukan file .xlsx asli. Untuk PDF, pilih tujuan
                <strong>Save as PDF</strong> di dialog cetak.
                Kedua fitur bekerja di browser tanpa layanan backend.
            </p>
        </section>
    </div>
</div>

@endsection