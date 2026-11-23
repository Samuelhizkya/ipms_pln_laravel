@extends('layouts.app')
@section('title', 'Statistik Kinerja')
@section('navbar-title', 'Statistik Kinerja')

@section('content')
@php
    $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $periodLabel = $months[$month] . ' ' . $year;
    $total = $actuals->count();
    $format = fn ($value) => $value === null ? '—' : number_format((float) $value, 2, ',', '.');
    $percent = fn ($value) => $value === null ? '—' : number_format((float) $value, 2, ',', '.') . '%';
    $nko = $nkoSummary['total_nko'] ?? null;
    $statuses = [
        ['label' => 'Tercapai', 'count' => $counts['achieved'], 'color' => '#237a37'],
        ['label' => 'Waspada', 'count' => $counts['warning'], 'color' => '#e9a11b'],
        ['label' => 'Belum Tercapai', 'count' => $counts['danger'], 'color' => '#df4545'],
        ['label' => 'Belum Dinilai', 'count' => $counts['missing'], 'color' => '#97a8b5'],
    ];
    $monthlyMeans = [];
    for ($m = 1; $m <= 12; $m++) {
        $valued = $yearlyActuals->filter(fn ($row) => (int) $row->periode->bulan === $m
            && $row->pencapaian !== null);
        $monthlyMeans[$m] = $m > (int) $month || $valued->isEmpty()
            ? null : (float) $valued->avg('pencapaian');
    }
    $available = array_values(array_filter($monthlyMeans, fn ($value) => $value !== null));
    $trendUpper = ceil(max(array_merge([140], $available)) / 20) * 20;
    $chartLower = floor(min(array_merge([0], $available)) / 20) * 20;
    $barUpper = ceil(max(array_merge([100], $available)) / 20) * 20;
    $trendY = fn ($value) => 22 + ($trendUpper - $value) / ($trendUpper - $chartLower) * 218;
    $barY = fn ($value) => 22 + ($barUpper - $value) / ($barUpper - $chartLower) * 218;
    $chartX = fn ($m) => 60 + ($m - 0.5) * 60;
    $svgNumber = fn ($value) => number_format((float) $value, 3, '.', '');
    $trendPath = '';
    $previous = false;
    foreach ($monthlyMeans as $m => $value) {
        if ($value === null) { $previous = false; continue; }
        $trendPath .= ($previous ? ' L ' : ' M ') . $svgNumber($chartX($m)) . ' ' . $svgNumber($trendY($value));
        $previous = true;
    }
    $segments = [];
    $position = 0;
    foreach ($statuses as $status) {
        $end = $position + ($total ? $status['count'] / $total * 100 : 0);
        $segments[] = $status['color'] . ' ' . $svgNumber($position) . '% ' . $svgNumber($end) . '%';
        $position = $end;
    }
    $donutBackground = $total ? 'conic-gradient(' . implode(', ', $segments) . ')' : '#e7edf3';
@endphp

<div class="us-page us-flutter-statistics">
    <section class="us-panel">
        <h2>Statistik Kinerja {{ $ulp->nama_ulp }}</h2>
        <p class="us-note">Ringkasan pencapaian KPI unit Anda pada periode terpilih.</p>
        <form method="GET" action="{{ route('ulp.statistik') }}" class="us-filters">
            <div class="us-field"><label>Unit Layanan Pelanggan</label><div class="us-unit">{{ $ulp->nama_ulp }}</div></div>
            <div class="us-field">
                <label for="usMonth">Bulan</label>
                <select id="usMonth" name="bulan" data-ipms-filter>
                    @foreach ($months as $number => $name)
                        <option value="{{ $number }}" @selected((int) $month === $number)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="us-field">
                <label for="usYear">Tahun</label>
                <select id="usYear" name="tahun" data-ipms-filter>
                    @foreach ($years as $availableYear)
                        <option value="{{ $availableYear }}" @selected((int) $year === (int) $availableYear)>{{ $availableYear }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="us-button">Tampilkan Data</button>
        </form>
    </section>

    <section class="us-panel">
        <h2><i data-lucide="chart-no-axes-combined"></i>Ringkasan Statistik ULP</h2>
        <p class="us-note">{{ $ulp->nama_ulp }} · {{ $periodLabel }}</p>
        <p class="us-note">Penilaian tersedia untuk <strong>{{ $assessedCount }}</strong> dari <strong>{{ $total }}</strong> entri; <strong>{{ $counts['missing'] }}</strong> belum dinilai.</p>
    </section>

    <div class="us-cards us-summary-two">
        <div class="us-card"><div class="us-label">Rata-rata Pencapaian</div><div class="us-value">{{ $percent($averageAchievement) }}</div><div class="us-caption">Rata-rata dari {{ $assessedCount }} entri yang memiliki nilai</div></div>
        <div class="us-card"><div class="us-label">NKO ULP</div><div class="us-value">{{ $format($nko) }}</div><div class="us-caption">{{ $nko === null ? 'Nilai NKO belum tersedia' : 'Nilai kinerja dari sistem' }}</div></div>
    </div>

    @if ($total === 0)
        <section class="us-panel"><p class="us-note">Belum ada entri realisasi pada periode ini. Grafik tren tetap menampilkan bulan yang memiliki nilai.</p></section>
    @endif

    <div class="us-columns us-chart-columns">
        <section class="us-panel">
            <h2><i data-lucide="chart-no-axes-combined"></i>Tren Pencapaian Bulanan</h2>
            <p class="us-note">Rata-rata pencapaian · Januari–{{ $months[$month] }} {{ $year }}</p>
            <p class="us-current-mean">{{ $months[$month] }} · {{ $percent($monthlyMeans[$month]) }}</p>
            <div class="us-chart-scroll">
                <svg class="us-monthly-svg" viewBox="0 0 800 300" role="img" aria-label="Tren rata-rata pencapaian bulanan {{ $ulp->nama_ulp }} tahun {{ $year }}">
                    <title>Tren Pencapaian Bulanan {{ $ulp->nama_ulp }}</title>
                    <desc>@foreach ($monthlyMeans as $m => $value){{ $months[$m] }}: {{ $value === null ? 'belum tersedia' : $percent($value) }}; @endforeach</desc>
                    @for ($tick = $chartLower; $tick <= $trendUpper; $tick += 20)
                        <line x1="60" x2="780" y1="{{ $svgNumber($trendY($tick)) }}" y2="{{ $svgNumber($trendY($tick)) }}" class="{{ $tick == 100 ? 'us-svg-reference' : 'us-svg-grid' }}" />
                        <text x="50" y="{{ $svgNumber($trendY($tick) + 4) }}" text-anchor="end" class="us-svg-label">{{ (int) $tick }}%</text>
                    @endfor
                    <path d="{{ $trendPath }}" class="us-svg-trend" />
                    @foreach ($monthlyMeans as $m => $value)
                        <text x="{{ $svgNumber($chartX($m)) }}" y="265" text-anchor="middle" class="us-svg-label">{{ substr($months[$m], 0, 3) }}</text>
                        @if ($value !== null)
                            <circle cx="{{ $svgNumber($chartX($m)) }}" cy="{{ $svgNumber($trendY($value)) }}" r="4.5" class="us-svg-point"><title>{{ $months[$m] }} · {{ $percent($value) }}</title></circle>
                        @endif
                    @endforeach
                </svg>
            </div>
            <p class="us-note">Garis putus-putus: acuan 100%. Bulan tanpa nilai dibiarkan kosong.</p>
        </section>

        <section class="us-panel">
            <h2><i data-lucide="chart-pie"></i>Distribusi Status Pencapaian KPI</h2>
            <p class="us-note">{{ $periodLabel }} · {{ $total }} entri realisasi</p>
            <div class="us-donut" style="background: {{ $donutBackground }};" role="img" aria-label="Distribusi {{ $total }} entri: {{ $counts['achieved'] }} tercapai, {{ $counts['warning'] }} waspada, {{ $counts['danger'] }} belum tercapai, {{ $counts['missing'] }} belum dinilai">
                <div class="us-donut-center"><strong>{{ $total }}</strong><small>entri</small></div>
            </div>
            <div class="us-donut-legend">
                @foreach ($statuses as $status)
                    <div class="us-donut-legend-row"><i style="background: {{ $status['color'] }};"></i><span>{{ $status['label'] }}</span><strong>{{ $status['count'] }} · {{ $total ? $percent($status['count'] / $total * 100) : '—' }}</strong></div>
                @endforeach
            </div>
            <p class="us-note">Tercapai ≥100% · Waspada 90%–&lt;100% · Belum tercapai &lt;90%. Persentase diagram dihitung dari seluruh entri pada periode ini.</p>
        </section>
    </div>

    <section class="us-panel">
        <h2><i data-lucide="chart-column"></i>Perbandingan Rata-rata ULP Saya</h2>
        <p class="us-note">{{ $ulp->nama_ulp }} · {{ $year }} · Data sampai {{ $months[$month] }}</p>
        <div class="us-chart-scroll">
            <svg class="us-monthly-svg us-comparison-svg" viewBox="0 0 800 320" role="img" aria-label="Perbandingan rata-rata pencapaian per bulan untuk {{ $ulp->nama_ulp }}">
                <title>Perbandingan Rata-rata ULP Saya</title>
                @for ($tick = $chartLower; $tick <= $barUpper; $tick += 20)
                    <line x1="60" x2="780" y1="{{ $svgNumber($barY($tick)) }}" y2="{{ $svgNumber($barY($tick)) }}" class="us-svg-grid" />
                    <text x="50" y="{{ $svgNumber($barY($tick) + 4) }}" text-anchor="end" class="us-svg-label">{{ (int) $tick }}%</text>
                @endfor
                @foreach ($monthlyMeans as $m => $value)
                    @if ($value !== null)
                        @php
                            $barTop = $value < 0 ? $barY(0) : $barY($value);
                            $barHeight = $value == 0 ? 3 : abs($barY($value) - $barY(0));
                        @endphp
                        <rect x="{{ $svgNumber($chartX($m) - 13) }}" y="{{ $svgNumber($barTop - ($value == 0 ? 1.5 : 0)) }}" width="26" height="{{ $svgNumber($barHeight) }}" rx="4" class="us-svg-bar"><title>{{ $months[$m] }} · {{ $percent($value) }}</title></rect>
                    @endif
                    <text x="{{ $svgNumber($chartX($m)) }}" y="265" text-anchor="middle" class="us-svg-label">{{ substr($months[$m], 0, 3) }}</text>
                    <text x="{{ $svgNumber($chartX($m)) }}" y="287" text-anchor="middle" class="us-svg-value">{{ $percent($value) }}</text>
                @endforeach
            </svg>
        </div>
        <p class="us-note">— Belum ada nilai atau bulan di luar periode pilihan. Bulan tanpa nilai tidak dianggap 0%.</p>
    </section>

    <section class="us-panel">
        <h2><i data-lucide="info"></i>Cara Membaca Statistik</h2>
        @if ($assessedCount === 0)
            <p class="us-note">Belum ada nilai pencapaian yang dapat disimpulkan pada periode ini.</p>
        @else
            <p class="us-note">{{ $counts['achieved'] }} dari {{ $assessedCount }} entri yang dinilai mencapai minimal 100%. {{ $counts['warning'] }} entri berada pada rentang waspada dan {{ $counts['danger'] }} entri di bawah 90%.</p>
        @endif
        <p class="us-note">Rata-rata dan tren memakai nilai pencapaian yang tersimpan; entri tanpa nilai tidak dianggap 0%. NKO ditampilkan terpisah dari rata-rata pencapaian.</p>
    </section>
</div>
@endsection
