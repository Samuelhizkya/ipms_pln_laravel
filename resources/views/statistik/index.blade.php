@extends('layouts.app')

@section('title', 'Statistik Kinerja')


@section('content')
<style>
    #stPage .st-panel:has(#stTargetChart) {
        display: none;
    }

    #stPage .st-panel:has(#stDonutChart) {
        grid-column: 1 / -1;
    }
</style>


<div
    class="st-page"
    id="stPage"
    data-api-base="{{ url('/data/monitoring') }}"
>
    <section class="st-panel">
        <h2 class="st-title"><i data-lucide="sliders-horizontal"></i> Filter Statistik Kinerja</h2>
        <div class="st-filters">
            <div class="st-field"><label for="stUlp">Pilih ULP</label><select id="stUlp"><option value="all">Seluruh ULP</option></select></div>
            <div class="st-field"><label for="stMonth">Bulan</label><select id="stMonth"></select></div>
            <div class="st-field"><label for="stYear">Tahun</label><select id="stYear"><option value="2024">2024</option><option value="2025">2025</option><option value="2026" selected>2026</option></select></div>
            <div class="st-field"><label for="stStatus">Status KPI</label><select id="stStatus"><option value="all">Semua Status</option><option value="achieved">Tercapai</option><option value="warning">Waspada</option><option value="danger">Belum Tercapai</option></select></div>
        </div>
        <div class="st-actions"><button id="stReset" class="st-btn st-btn-secondary" type="button">Reset</button><button id="stApply" class="st-btn" type="button">Tampilkan Data</button></div>
        <p id="stActiveFilter" class="st-info" aria-live="polite"></p>
    </section>

    <div class="st-cards">
        <div class="st-card"><span class="st-icon"><i data-lucide="clipboard-list"></i></span><h3>Jumlah Entri Dinilai</h3><strong id="stTotal">--</strong><small>Entri dengan pencapaian tersimpan; termasuk rincian pendukung</small></div>
        <div class="st-card"><span class="st-icon"><i data-lucide="gauge"></i></span><h3>NKO UP3 / ULP</h3><strong id="stNko">--</strong><small>Menunggu nilai resmi atau hasil impor Excel</small></div>
        <div class="st-card"><span class="st-icon"><i data-lucide="percent"></i></span><h3>Rata-rata Pencapaian</h3><strong id="stAverage">--</strong><small>Rerata pencapaian tersimpan per entri dinilai; bukan NKO</small></div>
    </div>

    <div class="st-charts">
        <section class="st-panel st-full">
            <h2 class="st-title"><i data-lucide="chart-no-axes-combined"></i> Tren Pencapaian Kinerja Bulanan</h2>
            <div class="st-chartbox tall"><canvas id="stTrendChart" aria-label="Grafik tren pencapaian KPI bulanan" role="img"></canvas></div>
            <p class="st-note">Rerata sederhana pencapaian tersimpan, termasuk rincian pendukung; bukan NKO. Mengikuti ULP, tahun, dan status sampai bulan terpilih. Bulan tanpa pencapaian tidak diisi nol.</p>
        </section>
        <section class="st-panel">
            <h2 class="st-title"><i data-lucide="chart-pie"></i> Distribusi Status Pencapaian KPI</h2>
            <div class="st-donut"><canvas id="stDonutChart" aria-label="Komposisi KPI tercapai, waspada dan belum tercapai" role="img"></canvas><div class="st-donut-center"><strong id="stDonutTotal">--</strong><small>KPI Dinilai</small></div></div>
            <div class="st-status-boxes"><div class="st-status-box achieved"><strong id="stDonutAchieved">--</strong>Tercapai</div><div class="st-status-box warning"><strong id="stDonutWarning">--</strong>Waspada</div><div class="st-status-box danger"><strong id="stDonutDanger">--</strong>Belum Tercapai</div></div>
            <p class="st-note">Warna abu-abu berarti belum dinilai. Batas kategori sementara sama dengan Monitoring: ≥100% tercapai, ≥90% waspada; lainnya belum tercapai. Batas resmi perlu disepakati tim.</p>
        </section>
        <section class="st-panel">
            <div class="st-grid-head"><h2 class="st-title"><i data-lucide="chart-column"></i> Target vs Realisasi KPI</h2><div class="st-inline-control"><label class="st-field" for="stIndicator" style="font-size:11px;color:#64748B">Indikator: <select id="stIndicator"></select></label></div></div>
            <div class="st-chartbox"><canvas id="stTargetChart" aria-label="Grafik perbandingan target dan realisasi satu indikator" role="img"></canvas></div>
            <p class="st-note" id="stTargetNote">Target dan realisasi dibandingkan dalam satuan yang sama, sesuai KPI yang dipilih.</p>
        </section>
        <section class="st-panel st-full">
            <h2 class="st-title"><i data-lucide="building-2"></i> Perbandingan Rata-rata Pencapaian Antar-ULP</h2>
            <div class="st-chartbox tall"><canvas id="stUlpChart" aria-label="Grafik perbandingan kinerja ULP" role="img"></canvas></div>
            <p class="st-note">Rata-rata pencapaian per ULP berdasarkan pencapaian tersimpan per entri, termasuk pendukung pada periode dan status terpilih, bukan ranking NKO resmi.</p>
        </section>
    </div>


</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>

@endsection
