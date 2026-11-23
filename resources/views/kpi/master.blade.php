@extends('layouts.app')

@section('title', 'Master KPI')
@section('navbar-title', 'Master KPI')

@section('content')
<section class="card master-kpi-page">
    <div class="master-kpi-heading">
        <div>
            <h2>Master KPI</h2>
            <p>
                Identitas indikator dan penetapan ULP
                yang tercatat di sistem.
            </p>
        </div>

        <div class="master-form-actions">
            <a
                href="{{ route('target.kpi') }}"
                class="realisasi-edit-button"
            >
                Kembali ke Data KPI
            </a>

            <a
                href="{{ route('master-kpi.create') }}"
                class="realisasi-edit-button is-primary"
            >
                Tambah KPI
            </a>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('master-kpi.index') }}"
        class="master-kpi-filters"
    >
        <div class="kpi-field">
            <label for="master-search">Cari KPI</label>
            <input
                id="master-search"
                name="q"
                type="search"
                maxlength="255"
                value="{{ $keyword }}"
                placeholder="Kode atau nama KPI..."
            >
        </div>

        <div class="kpi-field">
            <label for="master-status">Status master</label>
            <select id="master-status" name="status">
                <option value="">Semua status</option>
                <option value="aktif" @selected($status === 'aktif')>
                    Aktif
                </option>
                <option value="nonaktif" @selected($status === 'nonaktif')>
                    Nonaktif
                </option>
                <option value="kosong" @selected($status === 'kosong')>
                    Belum diisi
                </option>
            </select>
        </div>

        <button
            type="submit"
            class="realisasi-edit-button is-primary"
        >
            Terapkan
        </button>

        <a
            href="{{ route('master-kpi.index') }}"
            class="realisasi-edit-button"
        >
            Reset
        </a>
    </form>

    <div
        class="table-wrapper"
        role="region"
        aria-label="Daftar Master KPI"
        tabindex="0"
    >
        <table class="kpi-table">
            <thead>
                <tr>
                    <th scope="col">No.</th>
                    <th scope="col">Kode KPI</th>
                    <th scope="col">Nama KPI</th>
                    <th scope="col">Satuan</th>
                    <th scope="col">Bobot (%)</th>
                    <th scope="col">Polaritas</th>
                    <th scope="col">KPI Induk</th>
                    <th scope="col">Penetapan ULP</th>
                    <th scope="col">Status Master</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($kpis as $kpi)
                    @php
                        $parent = $parents->get($kpi->id_parent);
                        $assignments = $unitAssignments->get(
                            $kpi->id_kpi,
                            collect()
                        );
                    @endphp

                    <tr>
                        <td>{{ $kpis->firstItem() + $loop->index }}</td>
                        <td>{{ $kpi->kode_kpi }}</td>
                        <td>{{ $kpi->nama_kpi }}</td>
                        <td>{{ $kpi->satuan ?: '—' }}</td>

                        <td class="kpi-number">
                            {{ number_format((float) $kpi->bobot, 2, ',', '.') }}
                        </td>

                        <td>
                            {{ $kpi->polaritas
                                ? ucfirst($kpi->polaritas)
                                : 'Belum diisi' }}
                        </td>

                        <td>
                            @if ($parent)
                                {{ $parent->kode_kpi }}
                                <small class="master-kpi-secondary">
                                    {{ $parent->nama_kpi }}
                                </small>
                            @elseif ($kpi->id_parent !== null)
                                Referensi induk tidak ditemukan
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @forelse ($assignments as $assignment)
                                <div class="master-kpi-unit">
                                    {{ $assignment->nama_ulp
                                        ?: 'ULP tidak ditemukan' }}

                                    <small class="master-kpi-secondary">
                                        Penetapan:
                                        {{ $assignment->assignment_status }}

                                        @if ($assignment->unit_status !== 'aktif')
                                            · Unit tidak aktif/tidak tersedia
                                        @endif
                                    </small>
                                </div>
                            @empty
                                Belum ada penetapan
                            @endforelse
                        </td>

                        <td>
                            {{ $kpi->status
                                ? ucfirst($kpi->status)
                                : 'Belum diisi' }}
                        </td>
                        <td>
    <a
        href="{{ route('master-kpi.edit', ['kpi' => $kpi->id_kpi]) }}"
        class="realisasi-edit-button"
    >
        Edit
    </a>
</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="kpi-empty">
                            Tidak ada Master KPI yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="master-kpi-footer">
        <span>
            @if ($kpis->total())
                Menampilkan {{ $kpis->firstItem() }}–{{ $kpis->lastItem() }}
                dari {{ $kpis->total() }} KPI
            @else
                0 KPI
            @endif
        </span>

        <nav aria-label="Halaman daftar Master KPI">
            @if ($kpis->previousPageUrl())
                <a
                    href="{{ $kpis->previousPageUrl() }}"
                    class="realisasi-edit-button"
                >
                    Sebelumnya
                </a>
            @endif

            <span>
                {{ $kpis->currentPage() }} / {{ $kpis->lastPage() }}
            </span>

            @if ($kpis->nextPageUrl())
                <a
                    href="{{ $kpis->nextPageUrl() }}"
                    class="realisasi-edit-button"
                >
                    Berikutnya
                </a>
            @endif
        </nav>
    </div>
</section>
@endsection