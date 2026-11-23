@extends('layouts.app')

@section('title', 'Laporan Kinerja')
@section('navbar-title', 'Laporan Kinerja')

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

    $format = fn ($value) => $value === null
        ? '—'
        : number_format((float) $value, 2, ',', '.');

    $reportRows = collect();

    foreach ($actuals as $actual) {
        $reportRows->push([
            'kpi_id' => $actual->id_kpi,
            'code' => $actual->kpi?->kode_kpi ?? '—',
            'name' => $actual->kpi?->nama_kpi ?? '—',
            'support' => $actual->nama_pendukung,
            'unit' => $actual->satuan_pendukung
                ?: ($actual->kpi?->satuan ?? '—'),
            'target' => $actual->target_periode,
            'actual' => $actual->realisasi,
            'pct' => $actual->pencapaian,
        ]);
    }

    // Sertakan target KPI yang belum memiliki realisasi.
    $actualKpiIds = $actuals->pluck('id_kpi');

    foreach ($targets as $target) {
        if ($actualKpiIds->contains($target->id_kpi)) {
            continue;
        }

        $monthly = $target->targetBulanan
            ->firstWhere('bulan', $month);

        $reportRows->push([
            'kpi_id' => $target->id_kpi,
            'code' => $target->kpi?->kode_kpi ?? '—',
            'name' => $target->kpi?->nama_kpi ?? '—',
            'support' => null,
            'unit' => $target->kpi?->satuan ?? '—',
            'target' => $monthly?->nilai_target,
            'actual' => null,
            'pct' => null,
        ]);
    }

    $reportGroups = $reportRows
        ->groupBy('kpi_id')
        ->sortBy(function ($entries) {
            $digits = preg_replace(
                '/\D/',
                '',
                $entries->first()['code']
            );

            return $digits === ''
                ? PHP_INT_MAX
                : (int) $digits;
        });

    $entryNumber = 0;
@endphp

<div
    class="ul-page"
    data-unit="{{ $ulp->nama_ulp }}"
    data-period="{{ $periodLabel }}"
>
    <section class="ul-panel">
        <h2>Laporan Kinerja {{ $ulp->nama_ulp }}</h2>

        <div class="ul-note">
            Rekap target dan realisasi KPI unit Anda.
        </div>

        <form
            method="GET"
            action="{{ route('ulp.laporan') }}"
            class="ul-filters"
        >
            <div class="ul-field">
                <label>Unit Layanan Pelanggan</label>
                <div class="ul-unit">
                    {{ $ulp->nama_ulp }}
                </div>
            </div>

            <div class="ul-field">
                <label for="ulMonth">Bulan</label>
                <select id="ulMonth" name="bulan">
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

            <div class="ul-field">
                <label for="ulYear">Tahun</label>
                <select id="ulYear" name="tahun">
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

            <button type="submit" class="ul-button">
                Tampilkan Laporan
            </button>
        </form>

        <p class="ul-note">{{ $periodLabel }}</p>
    </section>

    <div class="ul-summary">
        <div class="ul-card">
            Entri Laporan
            <strong id="ulTotal">—</strong>
        </div>

        <div class="ul-card">
            Tercapai
            <strong id="ulAchieved">—</strong>
        </div>

        <div class="ul-card">
            Perlu Perhatian
            <strong id="ulAttention">—</strong>
        </div>

        <div class="ul-card">
            Belum Dinilai
            <strong id="ulMissing">—</strong>
        </div>
    </div>

    <section class="ul-panel">
        <h2>Rekapitulasi Kinerja</h2>

<div class="ul-note">
    Setiap indikator memiliki target dan realisasi tersendiri.
    Termasuk target KPI yang belum memiliki realisasi.
</div>

<div class="ul-note" id="ulNkoSummary">
    <strong>NKO {{ $ulp->nama_ulp }} · {{ $periodLabel }}</strong>

    @if (($nkoSummary['total_nko'] ?? null) !== null)
        <p>
            Nilai NKO:
            <strong>
                {{ number_format($nkoSummary['total_nko'], 2, ',', '.') }}
            </strong>
        </p>
    @elseif (($nkoSummary['status_nko'] ?? '') === 'periode_belum_tersedia')
        <p>Periode perhitungan NKO belum tersedia.</p>
    @else
        <p>Data NKO belum lengkap. Komponen yang belum dapat dihitung:</p>

        <ul>
            @foreach (($nkoSummary['rincian_nko'] ?? []) as $komponen)
                @if (($komponen['nilai_kpi'] ?? null) === null)
                    <li>
                        {{ $komponen['nama_kpi']
                            ?? 'KPI ID ' . $komponen['id_kpi'] }}
                    </li>
                @endif
            @endforeach
        </ul>
    @endif

    <p>
        NKO mengikuti unit dan periode laporan.
        Pencarian dan filter status hanya menyaring tabel rincian.
    </p>
</div>

        <div class="ul-tools">
            <div class="ul-field">
                <label for="ulSearch">Cari KPI / indikator</label>
                <input
                    id="ulSearch"
                    type="search"
                    placeholder="Kode, nama KPI, atau indikator..."
                >
            </div>

            <div class="ul-field">
                <label for="ulStatus">Status Pencapaian</label>
                <select id="ulStatus">
                    <option value="all">Semua status</option>
                    <option value="ok">Tercapai</option>
                    <option value="warn">Waspada</option>
                    <option value="bad">Belum tercapai</option>
                    <option value="off">Belum dinilai</option>
                </select>
            </div>

            <button id="ulPrint" type="button" class="ul-button">
                Cetak / Simpan PDF
            </button>
        </div>

        <div class="ul-table-wrap">
            <table class="ul-table" id="ulTable">
                <thead>
                    <tr>
                        <th>Kode KPI</th>
                        <th>Nama KPI</th>
                        <th>Indikator Pendukung</th>
                        <th>Satuan</th>
                        <th>Target Periode</th>
                        <th>Realisasi</th>
                        <th>Pencapaian</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody id="ulBody">
                    @foreach ($reportGroups as $groupId => $entries)
                        @foreach ($entries as $row)
                            @php
                                $entryNumber++;
                                $pct = $row['pct'];

                                if ($pct === null) {
                                    $status = 'off';
                                    $label = 'Belum dinilai';
                                } elseif ((float) $pct >= 100) {
                                    $status = 'ok';
                                    $label = 'Tercapai';
                                } elseif ((float) $pct >= 90) {
                                    $status = 'warn';
                                    $label = 'Waspada';
                                } else {
                                    $status = 'bad';
                                    $label = 'Belum tercapai';
                                }

                                $indicator = filled($row['support'])
                                    ? $row['support']
                                    : 'Indikator utama';

                                $searchText = implode(' ', [
                                    $row['code'],
                                    $row['name'],
                                    $indicator,
                                ]);
                            @endphp

                            <tr
                                class="{{ $loop->first ? 'ul-group-start' : '' }}"
                                data-entry
                                data-group="{{ $groupId }}"
                                data-code="{{ $row['code'] }}"
                                data-name="{{ $row['name'] }}"
                                data-search="{{ $searchText }}"
                                data-status="{{ $status }}"
                            >
                                <td class="ul-code">
                                    {{ $loop->first ? $row['code'] : '' }}
                                </td>

                                <td class="ul-kpi-name">
                                    {{ $loop->first ? $row['name'] : '' }}
                                </td>

                                <td class="ul-indicator">
                                    <div class="ul-indicator-content">
                                        <span class="ul-entry-number">
                                            {{ $entryNumber }}.
                                        </span>
                                        <span>{{ $indicator }}</span>
                                    </div>
                                </td>

                                <td>{{ $row['unit'] }}</td>

                                <td class="ul-number">
                                    {{ $format($row['target']) }}
                                </td>

                                <td class="ul-number">
                                    {{ $format($row['actual']) }}
                                </td>

                                <td class="ul-number">
                                    @if ($pct === null)
                                        —
                                    @else
                                        {{ $format($pct) }}%
                                    @endif
                                </td>

                                <td>
                                    <span class="ul-badge ul-{{ $status }}">
                                        {{ $label }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    <tr id="ulEmpty" hidden>
                        <td colspan="8" class="ul-empty">
                            Tidak ada entri sesuai filter.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p id="ulCount" class="ul-note" aria-live="polite"></p>

        <p class="ul-note">
            Ringkasan mengikuti hasil filter.
            Kategori sementara: tercapai ≥100%, waspada ≥90% dan
            &lt;100%, selebihnya belum tercapai.
            Tanda — berarti belum tersedia, bukan nol.
            Filter pencarian dan status juga berlaku pada hasil PDF.
        </p>
    </section>
</div>

@endsection