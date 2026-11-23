@extends('layouts.app')

@section('title', 'Ranking ULP')
@section('navbar-title', 'Ranking ULP')

@section('content')
@php
    $bulanNama = [
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

    $rankingItems = collect($dataRanking)->values();

    $tahunDaftar = collect([
        2024, 2025, 2026, 2027, (int) $tahun,
    ])->unique()->sort()->values();

    $pesertaNko = $rankingItems->filter(
        fn ($item) => $item->ranking !== null
    );
@endphp

<div
    class="rk-page"
    id="rkPage"
    data-period="{{ $namaBulan }} {{ $tahun }}"
>
    <section class="rk-panel">
        <h2 class="rk-title">
            <i data-lucide="sliders-horizontal"></i>
            Filter Periode Ranking
        </h2>

        <form method="GET" action="{{ url('/ranking') }}">
            <div class="rk-filters">
                <div class="rk-field">
                    <label for="rkMonth">Bulan</label>

                    <select id="rkMonth" name="bulan" data-ipms-filter>
                        @foreach ($bulanNama as $angka => $nama)
                            <option
                                value="{{ $angka }}"
                                @selected((int) $bulan === $angka)
                            >
                                {{ $nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="rk-field">
                    <label for="rkYear">Tahun</label>

                    <select id="rkYear" name="tahun" data-ipms-filter>
                        @foreach ($tahunDaftar as $tahunPilihan)
                            <option
                                value="{{ $tahunPilihan }}"
                                @selected((int) $tahun === (int) $tahunPilihan)
                            >
                                {{ $tahunPilihan }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="rk-actions">
                <a href="{{ url('/ranking') }}" class="rk-btn rk-btn-alt">
                    Reset
                </a>

                <button type="submit" class="rk-btn">
                    Tampilkan Ranking
                </button>
            </div>

            <p class="rk-active">
                Menampilkan data:
                <strong>{{ $namaBulan }} {{ $tahun }}</strong>
                · Status periode:
                <strong>{{ $statusPeriode }}</strong>
            </p>
        </form>
    </section>

    <div class="rk-summary">
        <div class="rk-scard">
            <div class="rk-sicon">
                <i data-lucide="building-2"></i>
            </div>
            <small>Jumlah ULP Terdaftar</small>
            <strong>{{ $ringkasan['jumlah_ulp'] ?? 0 }}</strong>
            <span>Seluruh ULP aktif dalam database</span>
        </div>

        <div class="rk-scard">
            <div class="rk-sicon">
                <i data-lucide="database"></i>
            </div>
            <small>ULP Memiliki Data</small>
            <strong>{{ $ringkasan['ulp_memiliki_data'] ?? 0 }}</strong>
            <span>ULP dengan catatan realisasi periode ini</span>
        </div>

        <div class="rk-scard">
            <div class="rk-sicon">
                <i data-lucide="clipboard-check"></i>
            </div>
            <small>Realisasi KPI Valid</small>
            <strong>{{ $ringkasan['total_kpi_valid'] ?? 0 }}</strong>
            <span>Jumlah KPI berstatus valid</span>
        </div>

        <div class="rk-scard">
            <div class="rk-sicon">
                <i data-lucide="clock-3"></i>
            </div>
            <small>Realisasi KPI Draft</small>
            <strong>{{ $ringkasan['total_kpi_draft'] ?? 0 }}</strong>
            <span>Jumlah KPI berstatus draft</span>
        </div>
    </div>

    <section class="rk-panel">
        <h2 class="rk-title">
            <i data-lucide="trophy"></i>
            Top 3 ULP dengan NKO Tertinggi
        </h2>

        <div class="rk-podium">
            @foreach ([2 => 'second', 1 => 'first', 3 => 'third'] as $posisi => $kelas)
                @php
                    $pemenang = $pesertaNko->filter(
                        fn ($item) => (int) $item->ranking === $posisi
                    );
                @endphp

                <div class="rk-winner {{ $kelas }}">
                    <div class="rk-medal">
                        <i data-lucide="trophy"></i>
                    </div>

                    <div class="rk-position">
                        PERINGKAT {{ $posisi }}
                    </div>

                    <h3>
                        {{ $pemenang->isNotEmpty()
                            ? $pemenang->pluck('nama_ulp')->implode(' / ')
                            : 'Belum tersedia' }}
                    </h3>

                    <div class="rk-score">
                        {{ $pemenang->isNotEmpty()
                            ? number_format((float) $pemenang->first()->nko, 2, ',', '.')
                            : '—' }}
                    </div>

                    <div class="rk-score-label">
                        {{ $pemenang->isNotEmpty()
                            ? 'Nilai NKO'
                            : 'Belum ada ULP pada peringkat ini' }}
                    </div>
                </div>
            @endforeach
        </div>

        <p class="rk-note">
            @if ($pesertaNko->isEmpty())
                Belum ada ULP dengan data NKO lengkap pada periode ini.
            @else
                Peringkat berdasarkan NKO tertinggi.
                Nilai sama mendapat peringkat sama.
                ULP tanpa NKO lengkap belum mendapat peringkat.
            @endif
            Status periode: {{ $statusPeriode }}.
        </p>
    </section>

    <section class="rk-panel">
        <div class="rk-flex-head">
            <h2 class="rk-title">
                <i data-lucide="list-ordered"></i>
                Daftar {{ $rankingItems->count() }} ULP
            </h2>

            <input
                class="rk-search"
                id="rkSearch"
                type="search"
                placeholder="Cari nama ULP..."
                aria-label="Cari nama ULP"
            >
        </div>

        <p class="rk-note">
            Nomor menunjukkan urutan daftar.
            Peringkat diberikan kepada ULP dengan NKO lengkap pada periode terpilih.
        </p>

        <div class="rk-table-wrap">
            <table class="rk-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama ULP</th>
                        <th>Total KPI Tercatat</th>
                        <th>KPI Valid</th>
                        <th>KPI Draft</th>
                        <th>KPI Error</th>
                        <th>NKO</th>
                        <th>Peringkat</th>
                        <th>Status Data</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody id="rkTableBody">
                    @foreach ($rankingItems as $item)
                        <tr data-name="{{ $item->nama_ulp }}">
                            <td>
                                <span class="rk-rank">
                                    {{ $loop->iteration }}
                                </span>
                            </td>

                            <td>
                                <span class="rk-unit">
                                    {{ $item->nama_ulp }}
                                </span>
                                <br>
                                <small class="rk-unit-code">
                                    {{ $item->kode_ulp }}
                                </small>
                            </td>

                            <td>
                                {{ number_format($item->total_kpi, 0, ',', '.') }}
                            </td>

                            <td>
                                {{ number_format($item->kpi_valid, 0, ',', '.') }}
                            </td>

                            <td>
                                {{ number_format($item->kpi_draft, 0, ',', '.') }}
                            </td>

                            <td>
                                {{ number_format($item->kpi_error, 0, ',', '.') }}
                            </td>

                            <td>
                                @if ($item->nko !== null)
                                    {{ number_format((float) $item->nko, 2, ',', '.') }}
                                @else
                                    <span class="rk-status-neutral">
                                        {{ $item->status_penilaian }}
                                    </span>
                                @endif
                            </td>

                            <td>{{ $item->ranking ?? '—' }}</td>

                            <td>
                                @if ($item->total_realisasi > 0)
                                    <span class="rk-pill achieved">
                                        Data tersedia
                                    </span>
                                @else
                                    <span class="rk-status-neutral">
                                        Belum ada data
                                    </span>
                                @endif
                            </td>

                            <td>
                                <button
                                    class="rk-detail"
                                    type="button"
                                    data-ulp="{{ $loop->index }}"
                                >
                                    Detail ULP
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    <tr
                        id="rkEmptyRow"
                        @if ($rankingItems->isNotEmpty()) hidden @endif
                    >
                        <td colspan="10" class="rk-empty">
                            Tidak ada ULP yang cocok dengan pencarian.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="rk-tablefoot">
            <span id="rkTableCount" aria-live="polite">
                Menampilkan {{ $rankingItems->count() }}
                dari {{ $rankingItems->count() }} ULP
            </span>

            <span>
                {{ $pesertaNko->count() }} ULP memiliki NKO lengkap.
            </span>
        </div>
    </section>


</div>

<section
    class="rk-page"
    id="rkDetailPage"
    aria-labelledby="rkDetailTitle"
    hidden
>
    <div class="rk-panel">
        <div class="rk-detail-heading">
            <h2 id="rkDetailTitle" tabindex="-1">
                Detail Kinerja ULP
            </h2>

            <p id="rkDetailSubtitle"></p>
        </div>

        <div class="rk-detailcards">
            <div class="rk-dcard">
                <small>Total KPI Tercatat</small>
                <strong id="rkDetailTotal">—</strong>
            </div>

            <div class="rk-dcard">
                <small>KPI Valid</small>
                <strong id="rkDetailValid">—</strong>
            </div>

            <div class="rk-dcard">
                <small>KPI Draft</small>
                <strong id="rkDetailDraft">—</strong>
            </div>

            <div class="rk-dcard">
                <small>KPI Error</small>
                <strong id="rkDetailError">—</strong>
            </div>
        </div>

        <div class="rk-detail-status">
            Ringkasan jumlah KPI mengikuti unit dan periode yang dipilih.
            Nilai NKO dan peringkat ditampilkan pada tabel utama apabila
            seluruh komponen NKO sudah dapat dihitung.
        </div>

        <div class="rk-detail-footer">
            <button
                id="rkBack"
                class="rk-btn rk-btn-alt"
                type="button"
            >
                Kembali ke Ranking
            </button>
        </div>
    </div>
</section>

<script type="application/json" id="rkData">
    @json($rankingItems)
</script>

@endsection