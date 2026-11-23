@extends('layouts.app')

@section('title', 'Infografis Kinerja')
@section('navbar-title', 'Infografis Kinerja')

@push('styles')
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>
@endpush

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

    $periodLabel = $months[$month] . ' ' . $year;

    // Hanya data unit dan periode yang diberikan controller.
    $indicatorRows = $actuals->map(function ($row) {
        return [
            'label' => implode(' · ', array_filter([
                $row->kpi?->kode_kpi,
                $row->kpi?->nama_kpi,
                $row->nama_pendukung,
            ])),
            'unit' => $row->satuan_pendukung
                ?: ($row->kpi?->satuan ?? '—'),
            'target' => $row->target_periode === null
                ? null
                : (float) $row->target_periode,
            'actual' => $row->realisasi === null
                ? null
                : (float) $row->realisasi,
            'pct' => $row->pencapaian === null
                ? null
                : (float) $row->pencapaian,
        ];
    })->values();

@endphp

<div
    class="ui-page"
    data-indicators="{{ $indicatorRows->toJson() }}"
    data-unit="{{ $ulp->nama_ulp }}"
    data-period="{{ $periodLabel }}"
>

    <section class="ui-panel">
        <h2>Infografis Kinerja {{ $ulp->nama_ulp }}</h2>

        <div class="ui-note">
            Peta wilayah dan pencapaian indikator KPI unit Anda.
        </div>

        <form
            method="GET"
            action="{{ route('ulp.infografis') }}"
            class="ui-filters"
        >
            <div class="ui-field">
                <label>Unit Layanan Pelanggan</label>
                <div class="ui-unit">{{ $ulp->nama_ulp }}</div>
            </div>

            <div class="ui-field">
                <label for="uiMonth">Bulan</label>

                <select id="uiMonth" name="bulan">
                    @foreach ($months as $number => $name)
                        <option
                            value="{{ $number }}"
                            @selected((int) $month === $number)
                        >
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ui-field">
                <label for="uiYear">Tahun</label>

                <select id="uiYear" name="tahun">
                    @foreach ($years as $availableYear)
                        <option
                            value="{{ $availableYear }}"
                            @selected((int) $year === (int) $availableYear)
                        >
                            {{ $availableYear }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="ui-button">
                Tampilkan Data
            </button>
        </form>

        <div class="ui-field ui-indicator ig-field">
            <label for="uiIndicator">Indikator KPI</label>

            <select
                id="uiIndicator"
                @disabled($indicatorRows->isEmpty())
            >
                @forelse ($indicatorRows as $row)
                    <option value="{{ $loop->index }}">
                        {{ $row['label'] }}
                    </option>
                @empty
                    <option value="">
                        Belum ada realisasi pada periode ini
                    </option>
                @endforelse
            </select>
        </div>

        <p class="ui-note">
            {{ $periodLabel }}.
            Pilihan indikator mengikuti entri realisasi yang tersedia.
        </p>
    </section>

    <div class="ui-cards">
        <div class="ui-card">
            <span>Entri Tercapai</span>
            <strong>{{ $counts['achieved'] }}</strong>
        </div>

        <div class="ui-card">
            <span>Entri Waspada</span>
            <strong>{{ $counts['warning'] }}</strong>
        </div>

        <div class="ui-card">
            <span>Entri Belum Tercapai</span>
            <strong>{{ $counts['danger'] }}</strong>
        </div>

        <div class="ui-card">
            <span>Entri Belum Dinilai</span>
            <strong>{{ $counts['missing'] }}</strong>
        </div>
    </div>

    <div class="ui-columns">
        <section class="ui-panel">
            <h2>Peta Kantor UP3 Palu dan ULP</h2>

            <div class="ui-legend">
                <span><i class="ui-dot" style="background:#FFC107"></i>Kantor UP3 Palu</span>
                <span><i class="ui-dot" style="background:#E03E3E"></i>ULP Kota Palu (3)</span>
                <span><i class="ui-dot" style="background:#168FC5"></i>ULP luar Kota Palu (7)</span>
            </div>

            <div
                id="uiMap"
                class="ui-map"
                data-unit="{{ $ulp->nama_ulp }}"
                data-count="{{ $actuals->count() }}"
                data-period="{{ $periodLabel }}"
                role="application"
                aria-label="Peta 10 kantor ULP dan kantor UP3 Palu"
            ></div>

            <p id="uiMapNote" class="ui-note">
                Lokasi kantor mengikuti data Flutter. Klik penanda untuk melihat
                alamat, koordinat, dan Google Maps. Warna menunjukkan wilayah kantor,
                bukan status pencapaian KPI. Peta memerlukan koneksi internet.
            </p>
        </section>

        <section class="ui-panel">
            <h2>Detail Indikator Terpilih</h2>

            <p id="uiDetailName" class="ui-detail-name">
                Belum ada indikator
            </p>

            <div class="ui-note">
                {{ $ulp->nama_ulp }} · {{ $periodLabel }}
            </div>

            <div class="ui-metric">
                <span>Satuan</span>
                <strong id="uiUnit">—</strong>
            </div>

            <div class="ui-metric">
                <span>Target periode</span>
                <strong id="uiTarget">—</strong>
            </div>

            <div class="ui-metric">
                <span>Realisasi</span>
                <strong id="uiActual">—</strong>
            </div>

            <div class="ui-metric">
                <span>Pencapaian</span>
                <strong id="uiPct">—</strong>
            </div>

            <span id="uiStatus" class="ui-status">
                Belum dinilai
            </span>

<p class="ui-note">
    Pencapaian indikator mengikuti data tersimpan tanpa dihitung ulang.
    Tanda — berarti belum tersedia, bukan nol.
    Detail ini hanya menampilkan indikator milik unit akun Anda.
</p>
        </section>
    </div>




</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

@endsection