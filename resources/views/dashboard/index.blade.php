@extends('layouts.app')

@section('title', 'Dashboard')


@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>


<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzxD1SMwM8HnUgzfWQF5QmA3M=" crossorigin="" />

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div
    class="dashboard-page"
    id="adminDashboard"
data-api-base="{{ url('/data/monitoring') }}"
data-ranking-url="{{ url('/ranking') }}"
data-target-url="{{ route('target.kpi') }}"
data-realisasi-url="{{ route('realisasi.kpi') }}"
    style="
        --dashboard-image-ulp: url('{{ asset('images/jumlah-ulp.png') }}');
        --dashboard-image-kpi: url('{{ asset('images/jumlah-kpi.jpeg') }}');
        --dashboard-image-nko: url('{{ asset('images/nko-up3.png') }}');
        --dashboard-image-problem: url('{{ asset('images/kpi-bermasalah.jpeg') }}');
    "
>

    <!-- TAMPILAN UTAMA SESUAI REFERENSI: PETA DAN RANKING BERDAMPINGAN -->

    <div class="dashboard-overview">

    <!-- INFOGRAFIS -->

    <section class="dash-panel map-panel">

        <div class="panel-heading">

            <h2 class="panel-title">

                <i data-lucide="map-pinned"></i>

                Infografis UP3 Palu dan ULP

            </h2>

            <button type="button" id="expandOfficeMap" class="detail-link">

                Perbesar Peta &rarr;

            </button>

        </div>

        <p class="ulp-map-intro">

            Klik penanda untuk melihat alamat kantor, koordinat, dan tautan Google Maps. Geser peta dengan mouse atau jari, gunakan scroll atau tombol + / − untuk memperbesar.

        </p>

        <div class="ulp-map-toolbar">

            <div class="ulp-map-legend" aria-label="Keterangan penanda lokasi">
<button type="button" class="ulp-legend-filter" id="showUp3Office" aria-pressed="false"><span class="ulp-legend-dot" style="background:#FFC107"></span>Kantor UP3 Palu</button>

                <button type="button" class="ulp-legend-filter" id="showPaluUlps" aria-pressed="false" title="Tampilkan hanya 3 ULP di Kota Palu"><span class="ulp-legend-dot" style="background:#E03E3E"></span>Wilayah Kota Palu (3 ULP)</button>

                <button type="button" class="ulp-legend-filter" id="showOutsideUlps" aria-pressed="false" title="Tampilkan hanya 7 ULP di luar Kota Palu"><span class="ulp-legend-dot" style="background:#168FC5"></span>Wilayah luar Kota Palu (7 ULP)</button>

            </div>

            <button class="ulp-map-reset" type="button" id="resetUlpMap">Lihat Semua Kantor</button>

        </div>

        <label class="office-chooser-label" for="officeChooser">Daftar kantor</label><select id="officeChooser" class="office-chooser"></select>
<div id="ulpMap" role="application" aria-label="Peta kantor UP3 Palu dan sepuluh ULP"></div>

        <p class="ulp-map-footnote">

            Penanda memakai koordinat kantor yang sama dengan aplikasi Flutter, sesuai tautan Google Maps yang dipilih. Warna menunjukkan lokasi wilayah dan bukan status kinerja. Peta membutuhkan internet untuk memuat OpenStreetMap.

        </p>

    </section>

        <section class="dash-panel dashboard-ranking-panel">

            <div class="panel-heading">

                <h2 class="panel-title"><i data-lucide="trophy"></i>Ranking ULP</h2>

                <a href="{{ url('/ranking') }}" class="detail-link">Lihat Detail &rarr;</a>

            </div>
<p class="simulation-caption">
    Daftar 10 ULP mengikuti bulan dan tahun terpilih.
    Piala menandai urutan 1–3 pada daftar. Peringkat resmi mengikuti data NKO yang tersedia.
</p>

<div id="dashboardRankingList" class="dashboard-ulp-list" role="list" aria-label="Daftar sepuluh ULP"></div>
            </section>
        </div>

    <!-- FILTER -->

    <div class="dash-panel filter-panel">

        <div class="filter-layout">

            <div class="filter-group">

                <label for="filterUlp">Pilih ULP</label>

                <select id="filterUlp">

                    <option value="all">Seluruh ULP</option>

                    @foreach ([

                        'ULP Palu Kota',

                        'ULP Kamonji',

                        'ULP Tawaeli',

                        'ULP Donggala',

                        'ULP Tambu',

                        'ULP Parigi',

                        'ULP Poso',

                        'ULP Tentena',

                        'ULP Kolonedale',

                        'ULP Bungku'

                    ] as $ulp)

                        <option value="{{ $ulp }}">{{ $ulp }}</option>

                    @endforeach

                </select>

            </div>

            <div class="filter-group">

                <label for="filterMonth">Bulan</label>

                <select id="filterMonth">

                    @foreach ([

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

                        12 => 'Desember'

                    ] as $number => $month)

                        <option value="{{ $number }}"

                            {{ $number === 10 ? 'selected' : '' }}>

                            {{ $month }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="filter-group">

                <label for="filterYear">Tahun</label>

                <select id="filterYear">

                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026" selected>2026</option>

                </select>

            </div>

            <button type="button"

                    class="filter-button"

                    id="applyFilter">

                Tampilkan

            </button>

            <!-- Periode aktif sekarang menyatu dengan kontrol filter -->

            <div class="period-label">

            <i data-lucide="calendar-days"></i>

            <div>

                <small>Periode Monitoring</small>

                <strong id="activePeriod">Oktober 2026</strong>

            </div>

        </div>

        </div>

    </div>

    <!-- EMPAT KARTU GRADASI -->

    <div class="summary-grid">

        <div class="summary-card summary-ulp">

            <div class="summary-info">

                <p>Jumlah ULP</p>

                <h2 id="ulpCount">10</h2>

                <small>Jumlah unit sesuai filter</small>

            </div>

            <div class="summary-icon">

                <i data-lucide="building-2"></i>

            </div>

        </div>

        <div class="summary-card summary-kpi">

            <div class="summary-info">

                <p>Jumlah Master KPI</p>

                <h2 id="masterCount">—</h2>

                <small>Seluruh entri master, termasuk induk dan turunan</small>

            </div>

            <div class="summary-icon">

                <i data-lucide="clipboard-list"></i>

            </div>

        </div>

        <div class="summary-card summary-nko">

            <div class="summary-info">

                <p>NKO UP3</p>

                <h2>--</h2>

                <small>Menunggu data nilai kinerja</small>

            </div>

            <div class="summary-icon">

                <i data-lucide="gauge"></i>

            </div>

        </div>

        <div class="summary-card summary-problem">

            <div class="summary-info">

                <p>Entri Perlu Perhatian</p>

                <h2 id="problemCount">—</h2>

                <small id="problemNote">Memuat data…</small>

            </div>

            <div class="summary-icon">

                <i data-lucide="alert-triangle"></i>

            </div>

        </div>

    </div>

    <!-- GRAFIK DAN STATISTIK -->

    <div class="performance-grid dashboard-statistics-only">



        <section class="dash-panel">

            <div class="panel-heading">

                <h2 class="panel-title">

                    <i data-lucide="chart-pie"></i>

                    Statistik Pencapaian KPI

                </h2>

                <a href="{{ url('/statistik') }}"

                   class="detail-link">

                    Lihat Detail &rarr;

                </a>

            </div>

            <div class="statistics-content">

                <div class="donut-wrapper">

                    <canvas id="statistikKpiChart"></canvas>

                    <div class="donut-center">

                        <strong id="assessedCount">—</strong>

                        <small>Entri Dinilai</small>

                    </div>

                </div>

                <div class="status-cards">

                    <div class="status-card status-achieved">

                        <strong id="count-achieved">—</strong>

                        <p>Tercapai</p>

                    </div>

                    <div class="status-card status-warning">

                        <strong id="count-warning">—</strong>

                        <p>Waspada</p>

                    </div>

                    <div class="status-card status-danger">

                        <strong id="count-danger">—</strong>

                        <p>Belum Tercapai</p>

                    </div>

                </div>

                <p class="chart-note" id="statusNote">Memuat data…</p>

            </div>

        </section>

    </div>

    <!-- RINGKASAN MONITORING KPI -->

    <div class="dashboard-attention-grid">

        <section class="dash-panel">

            <div class="panel-heading">

                <h2 class="panel-title"><i data-lucide="activity"></i>Monitoring KPI</h2>

                <a href="{{ url('/monitoring') }}" class="detail-link">Lihat Detail &rarr;</a>

            </div>

            <p class="simulation-caption">Ringkasan maksimal 3 entri dengan pencapaian di bawah 100%, mengikuti ULP dan periode terpilih. Lihat seluruh data pada halaman Monitoring KPI.</p>

            <div class="dashboard-monitoring-list" id="dashboardMonitoringList"></div>

        </section>

    </div>

    <!-- RINGKASAN LAPORAN KINERJA -->

    <section class="dash-panel table-panel">

        <div class="panel-heading">

            <h2 class="panel-title">

                <i data-lucide="file-text"></i>

                Laporan Kinerja

            </h2>

            <div class="table-actions">

                <input type="search"

                       id="searchKpi"

                       class="table-search"

                       placeholder="Cari indikator KPI...">

                <a href="{{ url('/laporan') }}"

                   class="detail-link">

                    Lihat Detail &rarr;

                </a>

            </div>

        </div>

        <div class="table-wrapper">

            <table class="kpi-table">

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Indikator KPI</th>

                        <th>Satuan</th>

                        <th>Target</th>

                        <th>Realisasi</th>

                        <th>Pencapaian</th>

                        <th>Status</th>

                    </tr>

                </thead>

                <tbody id="kpiTableBody"></tbody>

            </table>

        </div>

        <div class="table-footer">

            <span id="tableCount">Menampilkan 5 indikator</span>

            <span>Target periode dan realisasi dari database</span>

        </div>

    </section>

</div>

@endsection
