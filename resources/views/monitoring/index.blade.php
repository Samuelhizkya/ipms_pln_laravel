@extends('layouts.app')

@section('content')

<div
    class="mon-page"
    id="monitoringKpiPage"
    data-api-base="{{ url('/data/monitoring') }}"
>
    <section class="mon-panel" aria-label="Filter Monitoring KPI">
        <h2 class="mon-panel-title"><i data-lucide="sliders-horizontal"></i> Filter Monitoring</h2>
        <div class="mon-filters">
            <div class="mon-field"><label for="monUlp">Pilih ULP</label><select id="monUlp"><option value="all">Seluruh ULP</option></select></div>
            <div class="mon-field"><label for="monMonth">Bulan</label><select id="monMonth"></select></div>
            <div class="mon-field"><label for="monYear">Tahun</label><select id="monYear"><option value="2024">2024</option><option value="2025">2025</option><option value="2026" selected>2026</option></select></div>
            <div class="mon-field"><label for="monStatus">Status KPI</label><select id="monStatus"><option value="all">Semua Status</option><option value="achieved">Tercapai</option><option value="warning">Waspada</option><option value="danger">Belum Tercapai</option></select></div>
        </div>
        <div class="mon-filter-actions"><button class="mon-btn mon-btn-outline" id="monReset" type="button">Reset</button><button class="mon-btn" id="monApply" type="button">Tampilkan Data</button></div>
        <p class="mon-caption" id="monPeriodInfo" aria-live="polite"></p>
    </section>
    <div class="mon-summary">
        <div class="mon-summary-card"><div><small>Jumlah KPI</small><strong id="monTotal">0</strong><span>Indikator pada hasil filter</span></div><div class="mon-summary-icon"><i data-lucide="clipboard-list"></i></div></div>
        <div class="mon-summary-card"><div><small>KPI Tercapai</small><strong id="monAchieved">0</strong><span>Pencapaian memenuhi batas</span></div><div class="mon-summary-icon green"><i data-lucide="check-circle-2"></i></div></div>
        <div class="mon-summary-card"><div><small>KPI Waspada</small><strong id="monWarning">0</strong><span>Mendekati target</span></div><div class="mon-summary-icon yellow"><i data-lucide="alert-circle"></i></div></div>
        <div class="mon-summary-card"><div><small>Belum Tercapai</small><strong id="monDanger">0</strong><span>Memerlukan evaluasi</span></div><div class="mon-summary-icon red"><i data-lucide="alert-triangle"></i></div></div>
    </div>
    <section class="mon-panel">
        <div class="mon-section-top"><h2 class="mon-panel-title"><i data-lucide="table-2"></i> Daftar Monitoring KPI</h2><input id="monSearch" class="mon-search" type="search" placeholder="Cari kode, indikator, atau ULP..." aria-label="Cari indikator KPI" /></div>
        <div class="mon-table-scroll"><table class="mon-table"><thead><tr><th>No</th><th>Kode / Indikator</th><th>ULP</th><th>Satuan</th><th>Target</th><th>Realisasi</th><th>Pencapaian</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="monRows"></tbody></table></div>
        <div class="mon-table-footer"><span id="monCount">Memuat data...</span><div class="mon-pages"><button type="button" id="monPrev" aria-label="Halaman sebelumnya">&#8249;</button><span id="monPageText">1 / 1</span><button type="button" id="monNext" aria-label="Halaman berikutnya">&#8250;</button></div></div>
        <p class="mon-caption">Data dibaca dari database. Target adalah target periode; pencapaian mengikuti nilai tersimpan tanpa dihitung ulang. Kategori tampilan sementara: tercapai ≥100%, waspada ≥90%, selebihnya belum tercapai; batas ini perlu disepakati tim. Entri pendukung ditampilkan terpisah; jumlah entri bukan jumlah KPI unik.</p>
    </section>
</div>

<section
    class="mon-page mon-detail-page mon-panel"
    id="monModal"
    hidden
    aria-labelledby="monDetailTitle"
>
    <div class="mon-detail-page-heading">
        <div>
            <h2 id="monDetailTitle" tabindex="-1">
                Detail Indikator KPI
            </h2>

            <p id="monDetailSub" class="mon-caption"></p>
        </div>

    </div>

    <div id="monDetailStatus" class="mon-detail-page-status"></div>

    <div class="mon-detail-grid" id="monDetailMetrics"></div>

    <h3 class="mon-history-title">
        Riwayat Pencapaian 6 Bulan Terakhir
    </h3>

    <div
        class="mon-history"
        id="monHistory"
        role="img"
        aria-label="Grafik riwayat pencapaian enam bulan terakhir"
    ></div>

    <p class="mon-detail-note">
        Riwayat mengikuti data tersimpan untuk KPI, ULP,
        dan indikator pendukung yang sama.
        Tanda — berarti nilai belum tersedia, bukan nol.
        Pencapaian tidak dihitung ulang oleh tampilan.
    </p>

    <div class="mon-modal-footer">
        <button
            class="mon-btn mon-btn-outline"
            id="monCloseBottom"
            type="button"
        >
            Kembali ke Monitoring
        </button>
    </div>
</section>
@endsection
