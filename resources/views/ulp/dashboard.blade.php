@extends('layouts.app')
@section('title', 'Dashboard ULP')
@section('navbar-title', 'Dashboard ULP')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
@php
    $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $format = fn ($value) => $value === null ? '—' : number_format((float) $value, 2, ',', '.');
    $periodLabel = $months[$month] . ' ' . $year;
    $periodFilters = ['bulan' => $month, 'tahun' => $year];
    $totalEntries = $actuals->count();
    $attentionRows = $actuals->filter(fn ($row) => $row->pencapaian !== null
        && (float) $row->pencapaian < 100)->sortBy('pencapaian')->values();
    $rankingRows = $dashboardUlps->sort(function ($first, $second) {
        $firstRank = $first->ranking ?? PHP_INT_MAX;
        $secondRank = $second->ranking ?? PHP_INT_MAX;
        return $firstRank <=> $secondRank ?: strcmp($first->nama_ulp, $second->nama_ulp);
    })->take(10)->values();
    $distribution = [
        ['label' => 'Tercapai', 'count' => $counts['achieved'], 'color' => '#237A34'],
        ['label' => 'Waspada', 'count' => $counts['warning'], 'color' => '#F39C12'],
        ['label' => 'Belum Tercapai', 'count' => $counts['danger'], 'color' => '#E03E3E'],
        ['label' => 'Belum dinilai', 'count' => $counts['missing'], 'color' => '#94A3B8'],
    ];
    $position = 0;
    $segments = [];
    foreach ($distribution as $item) {
        $end = $position + ($totalEntries ? $item['count'] / $totalEntries * 100 : 0);
        $segments[] = $item['color'] . ' ' . number_format($position, 6, '.', '') . '% '
            . number_format($end, 6, '.', '') . '%';
        $position = $end;
    }
    $donutBackground = $totalEntries ? 'conic-gradient(' . implode(', ', $segments) . ')' : '#E7EDF3';
@endphp

<div class="ud-page ud-flutter-dashboard" id="ulpFlutterDashboard">
    <section class="ud-panel">
        <h2>Dashboard {{ $ulp->nama_ulp }}</h2>
        <p class="ud-note">Ringkasan kinerja unit Anda · {{ $periodLabel }}</p>
        <form method="GET" action="{{ route('ulp.dashboard') }}" class="ud-filters">
            <div class="ud-field"><label>Unit Layanan Pelanggan</label><div class="ud-unit">{{ $ulp->nama_ulp }}</div></div>
            <div class="ud-field">
                <label for="udMonth">Bulan</label>
                <select id="udMonth" name="bulan" data-ipms-filter>
                    @foreach ($months as $number => $name)
                        <option value="{{ $number }}" @selected((int) $month === $number)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ud-field">
                <label for="udYear">Tahun</label>
                <select id="udYear" name="tahun" data-ipms-filter>
                    @foreach ($years as $availableYear)
                        <option value="{{ $availableYear }}" @selected((int) $year === (int) $availableYear)>{{ $availableYear }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="ud-button">Tampilkan Data</button>
        </form>
    </section>

    <div class="ud-cards" style="--ud-image-ulp: url('{{ asset('images/jumlah-ulp.png') }}'); --ud-image-master: url('{{ asset('images/jumlah-kpi.jpeg') }}'); --ud-image-nko: url('{{ asset('images/nko-up3.png') }}'); --ud-image-attention: url('{{ asset('images/kpi-bermasalah.jpeg') }}');">
        <div class="ud-card"><div class="ud-card-label">Jumlah ULP</div><div class="ud-card-value">1</div><div class="ud-card-note">Unit sesuai akun yang login</div></div>
        <div class="ud-card"><div class="ud-card-label">Jumlah Master KPI</div><div class="ud-card-value">{{ $masterKpiCount }}</div><div class="ud-card-note">Master aktif, termasuk induk dan turunan</div></div>
        <div class="ud-card"><div class="ud-card-label">NKO ULP</div><div class="ud-card-value">{{ $format($nkoSummary['total_nko'] ?? null) }}</div><div class="ud-card-note">{{ ($nkoSummary['total_nko'] ?? null) === null ? 'Nilai belum tersedia' : 'Nilai kinerja organisasi' }}</div></div>
        <div class="ud-card"><div class="ud-card-label">Entri Perlu Perhatian</div><div class="ud-card-value">{{ $attentionRows->count() }}</div><div class="ud-card-note">Pencapaian &lt;100% · {{ $counts['missing'] }} belum dinilai</div></div>
    </div>

    <section class="ud-panel">
        <div class="ud-heading">
            <h2><i data-lucide="map-pinned"></i>Infografis Wilayah Kerja ULP</h2>
            <a class="ud-link" href="{{ route('ulp.infografis', $periodFilters) }}">Lihat Detail →</a>
        </div>
        <div class="ud-map-tools">
            <div class="ud-map-legend">
                <span><i style="background:#FFC107"></i>Kantor UP3 Palu</span>
                <span><i style="background:#E03E3E"></i>ULP Kota Palu (3)</span>
                <span><i style="background:#168FC5"></i>ULP luar Kota Palu (7)</span>
            </div>
        </div>
        <div id="udMap" class="ud-map" data-unit="{{ $ulp->nama_ulp }}" data-count="{{ $totalEntries }}" data-period="{{ $periodLabel }}" role="application" aria-label="Peta 10 kantor ULP dan UP3 Palu"></div>
        <p class="ud-note">Klik penanda untuk melihat alamat, koordinat, dan Google Maps. Warna menunjukkan wilayah kantor. Peta memerlukan koneksi internet.</p>
    </section>

    <div class="ud-columns">
        <section class="ud-panel">
            <div class="ud-heading"><h2><i data-lucide="flag"></i>Data &amp; Target KPI</h2><a href="{{ route('target.kpi', $periodFilters) }}" class="ud-link">Lihat Detail →</a></div>
            <p class="ud-note">{{ $targets->count() }} entri target · {{ $periodLabel }}</p>
            <div class="ud-preview-list">
                @forelse ($targets->take(3) as $target)
                    @php $monthlyTarget = $target->targetBulanan->firstWhere('bulan', $month); @endphp
                    <div class="ud-preview-row">
                        <div class="ud-preview-info"><strong>{{ $target->kpi?->nama_kpi ?? 'KPI' }}</strong><small>{{ $target->kpi?->kode_kpi ?? '—' }} · {{ $ulp->nama_ulp }}</small></div>
                        <strong class="ud-preview-score">{{ $format($monthlyTarget?->nilai_target) }} <small>{{ $target->kpi?->satuan ?? '' }}</small></strong>
                    </div>
                @empty
                    <p class="ud-note">Belum ada target pada tahun ini.</p>
                @endforelse
            </div>
        </section>
        <section class="ud-panel">
            <div class="ud-heading"><h2><i data-lucide="clipboard-check"></i>Realisasi KPI</h2><a href="{{ route('realisasi.kpi', $periodFilters) }}" class="ud-link">Lihat Detail →</a></div>
            <p class="ud-note">{{ $totalEntries }} entri realisasi · {{ $periodLabel }}</p>
            <div class="ud-preview-list">
                @forelse ($actuals->take(3) as $actual)
                    <div class="ud-preview-row">
                        <div class="ud-preview-info"><strong>{{ $actual->kpi?->nama_kpi ?? 'KPI' }}</strong><small>{{ $actual->kpi?->kode_kpi ?? '—' }} · {{ $ulp->nama_ulp }}@if ($actual->nama_pendukung) · {{ $actual->nama_pendukung }}@endif</small></div>
                        <strong class="ud-preview-score">{{ $format($actual->realisasi) }} <small>{{ $actual->satuan_pendukung ?: ($actual->kpi?->satuan ?? '') }}</small></strong>
                    </div>
                @empty
                    <p class="ud-note">Belum ada realisasi pada periode ini.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="ud-panel">
        <div class="ud-heading"><h2><i data-lucide="activity"></i>Monitoring KPI</h2><a href="{{ route('ulp.monitoring', $periodFilters) }}" class="ud-link">Lihat Detail →</a></div>
        <p class="ud-note">{{ $periodLabel }} · Entri dengan pencapaian di bawah 100%, dimulai dari nilai terendah.</p>
        <div class="ud-preview-list">
            @forelse ($attentionRows->take(3) as $actual)
                <div class="ud-preview-row">
                    <div class="ud-preview-info"><strong>{{ $actual->kpi?->nama_kpi ?? 'KPI' }}</strong><small>{{ $actual->kpi?->kode_kpi ?? '—' }} · {{ $ulp->nama_ulp }}@if ($actual->nama_pendukung) · {{ $actual->nama_pendukung }}@endif</small></div>
                    <strong class="ud-preview-score {{ (float) $actual->pencapaian >= 90 ? 'ud-score-warning' : 'ud-score-danger' }}">{{ $format($actual->pencapaian) }}%</strong>
                </div>
            @empty
                <p class="ud-note">Tidak ada entri dengan pencapaian di bawah 100%.</p>
            @endforelse
        </div>
    </section>

    <section class="ud-panel">
        <div class="ud-heading"><h2><i data-lucide="trophy"></i>Ranking ULP</h2><a href="{{ route('ulp.ranking', $periodFilters) }}" class="ud-link">Lihat Detail →</a></div>
        <p class="ud-note">{{ $periodLabel }} · Daftar 10 ULP. Piala menunjukkan urutan 1–3 pada daftar; peringkat resmi mengikuti NKO.</p>
        <div class="ud-rank-list" role="list">
            @forelse ($rankingRows as $rankingUnit)
                <div class="ud-rank-row {{ (int) $rankingUnit->id_ulp === (int) $ulp->id_ulp ? 'ud-ranking-own' : '' }}" role="listitem">
                    <span class="ud-rank-badge {{ $loop->iteration <= 3 ? 'ud-podium-' . $loop->iteration : '' }}" aria-label="Urutan daftar {{ $loop->iteration }}">
                        @if ($loop->iteration <= 3)<i data-lucide="trophy"></i><small>{{ $loop->iteration }}</small>@else{{ $loop->iteration }}@endif
                    </span>
                    <div class="ud-rank-info"><strong>{{ $rankingUnit->nama_ulp }}@if ((int) $rankingUnit->id_ulp === (int) $ulp->id_ulp)<span class="ud-ranking-unit-label">Unit Anda</span>@endif</strong><small>{{ ($rankingUnit->ranking ?? null) === null ? 'Belum berperingkat' : 'Peringkat ' . $rankingUnit->ranking }}</small></div>
                    <strong class="ud-rank-score">NKO {{ $format($rankingUnit->nko ?? null) }}</strong>
                </div>
            @empty
                <p class="ud-note">Daftar ULP belum tersedia.</p>
            @endforelse
        </div>
    </section>

    <section class="ud-panel">
        <div class="ud-heading"><h2><i data-lucide="chart-pie"></i>Statistik · Distribusi Status Pencapaian KPI</h2><a href="{{ route('ulp.statistik', $periodFilters) }}" class="ud-link">Lihat Detail →</a></div>
        <p class="ud-note">{{ $ulp->nama_ulp }} · {{ $periodLabel }}</p>
        <div class="ud-distribution">
            <div class="ud-donut" style="background: {{ $donutBackground }};" role="img" aria-label="Distribusi {{ $totalEntries }} entri: {{ $counts['achieved'] }} tercapai, {{ $counts['warning'] }} waspada, {{ $counts['danger'] }} belum tercapai, {{ $counts['missing'] }} belum dinilai">
                <div class="ud-donut-center"><strong>{{ $totalEntries }}</strong><small>Entri realisasi</small></div>
            </div>
            <div class="ud-distribution-legend">
                @foreach ($distribution as $item)
                    <div class="ud-distribution-row"><i style="background: {{ $item['color'] }}"></i><span>{{ $item['label'] }}</span><strong>{{ $item['count'] }} · {{ number_format($totalEntries ? $item['count'] / $totalEntries * 100 : 0, 1, ',', '.') }}%</strong></div>
                @endforeach
            </div>
        </div>
        <p class="ud-note">Persentase mengikuti {{ $totalEntries }} entri realisasi unit Anda, termasuk rincian pendukung. Entri tanpa nilai tetap belum dinilai.</p>
    </section>

    <section class="ud-panel">
        <div class="ud-heading"><h2><i data-lucide="file-text"></i>Laporan Kinerja</h2><a href="{{ route('ulp.laporan', $periodFilters) }}" class="ud-link">Lihat Detail →</a></div>
        <p class="ud-note">{{ $totalEntries }} entri realisasi · {{ $periodLabel }}</p>
        <div class="ud-preview-list">
            @forelse ($actuals->take(3) as $actual)
                <div class="ud-preview-row">
                    <div class="ud-preview-info"><strong>{{ $actual->kpi?->nama_kpi ?? 'KPI' }}</strong><small>{{ $actual->kpi?->kode_kpi ?? '—' }} · {{ $ulp->nama_ulp }}@if ($actual->nama_pendukung) · {{ $actual->nama_pendukung }}@endif</small></div>
                    <strong class="ud-preview-score">{{ $format($actual->pencapaian) }}@if ($actual->pencapaian !== null)%@endif</strong>
                </div>
            @empty
                <p class="ud-note">Belum ada realisasi pada periode ini.</p>
            @endforelse
        </div>
    </section>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection
