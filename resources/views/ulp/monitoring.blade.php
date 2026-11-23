@extends('layouts.app')

@section('title', 'Monitoring KPI')
@section('navbar-title', 'Monitoring KPI')


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

    $format = fn ($value) => $value === null
        ? '—'
        : number_format((float) $value, 2, ',', '.');

    $periodLabel = $months[$month] . ' ' . $year;
@endphp

<div class="um-page">

    <section class="um-panel">
        <h2>Monitoring KPI {{ $ulp->nama_ulp }}</h2>

        <div class="um-note">
            Pantau target, realisasi, dan pencapaian KPI unit Anda.
        </div>

        <form
            method="GET"
            action="{{ route('ulp.monitoring') }}"
            class="um-filters"
        >
            <div class="um-field">
                <label>Unit Layanan Pelanggan</label>
                <div class="um-unit">{{ $ulp->nama_ulp }}</div>
            </div>

            <div class="um-field">
                <label for="umMonth">Bulan</label>

                <select id="umMonth" name="bulan">
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

            <div class="um-field">
                <label for="umYear">Tahun</label>

                <select id="umYear" name="tahun">
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

            <button type="submit" class="um-button">
                Tampilkan Data
            </button>
        </form>

        <p class="um-note">
            Periode monitoring: {{ $periodLabel }}
        </p>
    </section>

    <div class="um-cards">
        <div class="um-card">
            <div class="um-card-label">Entri Tercapai</div>
            <div class="um-card-value">{{ $counts['achieved'] }}</div>
            <div class="um-card-note">Pencapaian minimal 100%</div>
        </div>

        <div class="um-card">
            <div class="um-card-label">Entri Waspada</div>
            <div class="um-card-value">{{ $counts['warning'] }}</div>
            <div class="um-card-note">Pencapaian ≥90% dan &lt;100%</div>
        </div>

        <div class="um-card">
            <div class="um-card-label">Entri Belum Tercapai</div>
            <div class="um-card-value">{{ $counts['danger'] }}</div>
            <div class="um-card-note">Pencapaian di bawah 90%</div>
        </div>

        <div class="um-card">
            <div class="um-card-label">Entri Belum Dinilai</div>
            <div class="um-card-value">{{ $counts['missing'] }}</div>
            <div class="um-card-note">Nilai pencapaian belum tersedia</div>
        </div>
    </div>

    <section class="um-panel">
        <div class="um-heading">
            <h2>Monitoring Realisasi KPI</h2>

            <div class="um-search-field">
                <label for="umSearch">Cari KPI / indikator</label>
                <input
                    id="umSearch"
                    class="um-search"
                    type="search"
                    placeholder="Kode, nama KPI, atau indikator..."
                >
            </div>
        </div>

        <div class="um-note">
            {{ $ulp->nama_ulp }} · {{ $periodLabel }}.
            Nilai mengikuti data tersimpan; indikator pendukung
            ditampilkan sebagai baris tersendiri.
        </div>

        <div class="um-table-wrap">
            <table class="um-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode KPI</th>
                        <th>KPI / Indikator Pendukung</th>
                        <th>Satuan</th>
                        <th>Target Periode</th>
                        <th>Realisasi</th>
                        <th>Pencapaian</th>
                        <th>Status Pencapaian</th>
                    </tr>
                </thead>

                <tbody id="umTableBody">
                    @foreach ($actuals as $actual)
                        @php
                            $pct = $actual->pencapaian;

                            if ($pct === null) {
                                $statusLabel = 'Belum dinilai';
                                $statusClass = 'um-missing';
                            } elseif ((float) $pct >= 100) {
                                $statusLabel = 'Tercapai';
                                $statusClass = 'um-achieved';
                            } elseif ((float) $pct >= 90) {
                                $statusLabel = 'Waspada';
                                $statusClass = 'um-warning';
                            } else {
                                $statusLabel = 'Belum tercapai';
                                $statusClass = 'um-danger';
                            }

                            $searchText = implode(' ', [
                                $actual->kpi?->kode_kpi ?? '',
                                $actual->kpi?->nama_kpi ?? '',
                                $actual->nama_pendukung ?? '',
                            ]);
                        @endphp

                        <tr data-search="{{ $searchText }}">
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                {{ $actual->kpi?->kode_kpi ?? '—' }}
                            </td>

                            <td class="um-name">
                                {{ $actual->kpi?->nama_kpi ?? '—' }}

                                @if ($actual->nama_pendukung)
                                    <div class="um-note">
                                        {{ $actual->nama_pendukung }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                {{ $actual->satuan_pendukung
                                    ?: ($actual->kpi?->satuan ?? '—') }}
                            </td>

                            <td class="um-number">
                                {{ $format($actual->target_periode) }}
                            </td>

                            <td class="um-number">
                                {{ $format($actual->realisasi) }}
                            </td>

                            <td class="um-number">
                                @if ($pct === null)
                                    —
                                @else
                                    {{ $format($pct) }}%
                                @endif
                            </td>

                            <td>
                                <span class="um-badge {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @endforeach

                    <tr
                        id="umEmptyRow"
                        @if ($actuals->isNotEmpty()) hidden @endif
                    >
                        <td colspan="8" class="um-empty">
                            Tidak ada realisasi yang sesuai.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p id="umTableCount" class="um-note" aria-live="polite">
            Menampilkan {{ $actuals->count() }}
            dari {{ $actuals->count() }} entri realisasi.
        </p>

        <p class="um-note">
            Kategori pencapaian masih menggunakan batas sementara.
            Status ini berbeda dari status valid, draft, atau error
            pada pencatatan data. Tanda — berarti nilai belum tersedia.
        </p>
    </section>

</div>

@endsection