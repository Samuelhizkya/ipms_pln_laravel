@extends('layouts.app')

@section('title', 'Infografis Kinerja')

@section('content')

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


<div
    class="ig-page"
    id="igPage"
    data-api-base="{{ url('/data/monitoring') }}"
>

    <section class="ig-panel ig-filter">
        <div class="ig-panelhead">
            <h2 class="ig-title">
                <i data-lucide="sliders-horizontal"></i>
                Filter Infografis Kinerja
            </h2>
        </div>

        <div class="ig-filtergrid">
            <div class="ig-field">
                <label for="igMode">Mode Penilaian</label>
                <select id="igMode">
                    <option value="nko">Mode NKO</option>
                    <option value="kpi">Mode KPI</option>
                </select>
            </div>

            <div class="ig-field">
                <label for="igUlp">Pilih ULP</label>
                <select id="igUlp">
                    <option value="all">Seluruh ULP</option>
                </select>
            </div>

            <div class="ig-field">
                <label for="igKpi">Indikator KPI</label>
                <select id="igKpi" disabled></select>
            </div>

            <div class="ig-field">
                <label for="igMonth">Bulan</label>
                <select id="igMonth"></select>
            </div>

            <div class="ig-field">
                <label for="igYear">Tahun</label>
                <select id="igYear">
                    <option selected>2026</option>
                </select>
            </div>
        </div>

        <div class="ig-filterbottom">
            <p id="igAppliedLabel" aria-live="polite">
                Memuat data kinerja…
            </p>

            <div class="ig-actions">
                <button type="button" class="ig-btn secondary" id="igReset">
                    Reset
                </button>
                <button type="button" class="ig-btn" id="igApply">
                    Tampilkan Data
                </button>
            </div>
        </div>
    </section>

    <div class="ig-mapgrid">
        <section class="ig-panel">
            <div class="ig-panelhead">
                <h2 class="ig-title">
                    <i data-lucide="map-pinned"></i>
                    Peta Wilayah Kerja UP3 Palu
                </h2>
                <button type="button" class="ig-btn secondary" id="igMapReset">
                    Lihat Semua
                </button>
            </div>

            <div
                id="igLegend"
                class="ig-key"
                aria-label="Legenda warna penanda peta"
            ></div>

            <div class="ig-office-picker">
                <label for="igOfficePicker">Pilih lokasi kantor</label>
                <select id="igOfficePicker"><option value="">Pilih kantor…</option></select>
            </div>
            <div
                id="igMap"
                class="ig-map"
                aria-label="Peta interaktif 10 kantor ULP dan kantor UP3 Palu"
                role="application"
            ></div>

            <p class="ig-note">
                Klik penanda atau pilih kantor untuk melihat alamat dan lokasi.
                Warna penanda menunjukkan wilayah kantor. Status pencapaian tersedia
                pada daftar ULP. Peta memerlukan internet untuk memuat OpenStreetMap.
            </p>
        </section>

        <section class="ig-panel">
            <div class="ig-panelhead">
                <h2 class="ig-title">
                    <i data-lucide="trophy"></i>
                    <span id="igRankTitle">Daftar Kinerja ULP</span>
                </h2>
            </div>

            <p class="ig-subtitle" id="igRankSubtitle">
                Memuat data ULP dari database.
            </p>

            <div class="ig-rank-scroll">
                <div class="ig-ranklist" id="igRankList"></div>
            </div>

            <p class="ig-note" id="igRankFootnote">
                Status KPI sementara: tercapai ≥100%, waspada ≥90%,
                selebihnya belum tercapai. NKO dan peringkat resmi
                belum tersedia.
            </p>
        </section>
    </div>

    <section class="ig-panel ig-office-detail">
        <div class="ig-panelhead">
            <h2 class="ig-title"><i data-lucide="building-2"></i>Detail Kantor Terpilih</h2>
            <button type="button" class="ig-btn secondary" id="igClearSelection">Hapus Pilihan</button>
        </div>
        <h3 id="igDetailName">Pilih kantor pada peta</h3>
        <p class="ig-subtitle" id="igOfficeRegion">—</p>
        <div class="ig-office-grid">
            <div><span class="ig-office-label">Alamat kantor</span><p id="igOfficeAddress">—</p></div>
            <div><span class="ig-office-label">Koordinat kantor</span><p id="igOfficeCoordinates">—</p></div>
        </div>
        <p class="ig-info-strip" id="igOfficeScore" aria-live="polite">Pilih kantor untuk melihat detail.</p>
        <a class="ig-btn" id="igOfficeMaps" target="_blank" rel="noopener noreferrer" hidden>Buka Google Maps ↗</a>
    </section>
</div>
@endsection
