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

    $tahunPilihanList = collect([2024, 2025, 2026, 2027, (int) $tahun])
        ->unique()
        ->sort()
        ->values();

    $rankingRows = collect($dataRanking)->values();
@endphp

<div class="rk-page" id="rkPage">

    <section class="rk-panel">
        <h2 class="rk-title">
            <i data-lucide="sliders-horizontal"></i>
            Filter Periode Ranking
        </h2>

        <form method="GET" action="{{ route('ulp.ranking') }}">
            <div class="rk-filters">
                <div class="rk-field">
                    <label for="rkMonth">Bulan</label>

                    <select id="rkMonth" name="bulan">
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

                    <select id="rkYear" name="tahun">
                        @foreach ($tahunPilihanList as $tahunPilihan)
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
                <a
                    href="{{ route('ulp.ranking') }}"
                    class="rk-btn rk-btn-alt"
                >
                    Reset
                </a>

                <button type="submit" class="rk-btn">
                    Tampilkan Ranking
                </button>
            </div>

            <p class="rk-note">
                Menampilkan periode:
                <strong>{{ $namaBulan }} {{ $tahun }}</strong>
                · Status periode:
                <strong>{{ $statusPeriode }}</strong>
            </p>
        </form>
    </section>

    <section class="rk-panel">
        <h2 class="rk-title">
            <i data-lucide="trophy"></i>
            Top 3 ULP dengan NKO Tertinggi
        </h2>

 @php
    $pesertaNko = $rankingRows->filter(
        fn ($item) => $item->ranking !== null
    );
@endphp

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
                    ? 'Hasil NKO backend'
                    : 'Belum ada ULP pada peringkat ini' }}
            </div>
        </div>
    @endforeach
</div>

<p class="rk-note">
    @if ($pesertaNko->isEmpty())
        Belum ada ULP dengan data NKO lengkap pada periode ini.
    @else
        Peringkat berdasarkan NKO lengkap, dari nilai tertinggi.
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
                Klasemen Lengkap {{ $rankingRows->count() }} ULP
            </h2>

            <label for="rkSearch" class="rk-hidden">
                Cari nama ULP
            </label>

            <input
                id="rkSearch"
                class="rk-search"
                type="search"
                placeholder="Cari nama ULP..."
            >
        </div>

        <p class="rk-note">
            Daftar menampilkan seluruh ULP aktif.
            Nomor urut daftar bukan peringkat.
            NKO dan peringkat ditampilkan setelah penilaian resmi tersedia.
        </p>

        <div class="rk-table-wrap">
            <table class="rk-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama ULP</th>
                        <th>NKO</th>
                        <th>Peringkat</th>
                    </tr>
                </thead>

                <tbody id="rkTableBody">
                    @foreach ($rankingRows as $item)
                        <tr
                            data-name="{{ strtolower($item->nama_ulp) }}"
                            class="{{ (int) $item->id_ulp === (int) $unitAndaId
                                ? 'rk-own-row'
                                : '' }}"
                        >
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <span class="rk-unit">
                                    {{ $item->nama_ulp }}
                                </span>

                                @if ((int) $item->id_ulp === (int) $unitAndaId)
                                    <span class="rk-status-neutral">
                                        Unit Anda
                                    </span>
                                @endif
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
                        </tr>
                    @endforeach

                    <tr
                        id="rkEmptyRow"
                        @if ($rankingRows->isNotEmpty()) hidden @endif
                    >
                        <td colspan="4" class="rk-empty">
                            Tidak ada ULP yang sesuai.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="rk-tablefoot">
            <span id="rkTableCount" aria-live="polite">
                Menampilkan {{ $rankingRows->count() }}
                dari {{ $rankingRows->count() }} ULP
            </span>

            <span>NKO dan peringkat resmi belum tersedia.</span>
        </div>
    </section>

</div>

@endsection