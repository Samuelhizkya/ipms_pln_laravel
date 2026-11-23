@extends('layouts.app')

@section('title', $record->exists ? 'Edit Master KPI' : 'Tambah Master KPI')
@section('navbar-title', $record->exists ? 'Edit Master KPI' : 'Tambah Master KPI')

@section('content')
<section class="card master-form-card">
    <div class="master-kpi-heading">
        <div>
            <h2>{{ $record->exists ? 'Edit Master KPI' : 'Tambah Master KPI' }}</h2>
            <p>Lengkapi definisi indikator sesuai data yang disepakati.</p>
        </div>

        <a
            href="{{ route('master-kpi.index') }}"
            class="realisasi-edit-button"
        >
            Kembali
        </a>
    </div>

    @if ($used)
        <p class="master-form-note">
            KPI ini sudah memiliki target atau realisasi.
            Definisi ditampilkan sebagai informasi; hanya status
            yang dapat diubah untuk menjaga konsistensi data.
        </p>
    @else
        <p class="master-form-note">
            Nama indikator harus sesuai dengan template impor.
            Polaritas kosong berarti belum ditetapkan.
            KPI baru tidak otomatis masuk ke pengelompokan NKO dashboard.
        </p>
    @endif

    @error('version')
        <div class="realisasi-edit-conflict" role="alert">
            <p>{{ $message }}</p>
            <a href="{{ route('master-kpi.edit', ['kpi' => $record->id_kpi]) }}">
                Muat ulang formulir
            </a>
        </div>
    @enderror

    <form
        method="POST"
        action="{{ $record->exists
            ? route('master-kpi.update', ['kpi' => $record->id_kpi])
            : route('master-kpi.store') }}"
    >
        @csrf

        @if ($record->exists)
            @method('PUT')
            <input
                type="hidden"
                name="version"
                value="{{ old('version', $version) }}"
            >
        @endif

        <div class="master-form-grid">
            <div class="kpi-field">
                <label for="master-code">Kode KPI</label>
                <input
                    id="master-code"
                    name="kode_kpi"
                    type="text"
                    maxlength="30"
                    value="{{ $record->exists
                        ? $record->kode_kpi
                        : old('kode_kpi') }}"
                    required
                    @readonly($record->exists)
                >
                @error('kode_kpi')
                    <small class="master-form-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="kpi-field">
                <label for="master-parent">KPI induk</label>

                @if ($record->exists)
                    @php
                        $parent = $parents->firstWhere('id_kpi', $record->id_parent);
                    @endphp

                    <input
                        id="master-parent"
                        type="text"
                        value="{{ $parent
                            ? $parent->kode_kpi . ' — ' . $parent->nama_kpi
                            : ($record->id_parent ? 'Referensi induk tidak ditemukan' : 'Tanpa induk') }}"
                        readonly
                    >
                @else
                    <select id="master-parent" name="id_parent">
                        <option value="">Tanpa induk</option>

                        @foreach ($parents as $parent)
                            <option
                                value="{{ $parent->id_kpi }}"
                                @selected((string) old('id_parent') === (string) $parent->id_kpi)
                            >
                                {{ $parent->kode_kpi }} — {{ $parent->nama_kpi }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @error('id_parent')
                    <small class="master-form-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="kpi-field master-form-wide">
                <label for="master-name">Nama KPI</label>
                <input
                    id="master-name"
                    name="nama_kpi"
                    type="text"
                    maxlength="255"
                    value="{{ $used ? $record->nama_kpi : old('nama_kpi', $record->nama_kpi) }}"
                    required
                    @readonly($used)
                >
                @error('nama_kpi')
                    <small class="master-form-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="kpi-field">
                <label for="master-unit">Satuan</label>
                <input
                    id="master-unit"
                    name="satuan"
                    type="text"
                    maxlength="50"
                    value="{{ $used ? $record->satuan : old('satuan', $record->satuan) }}"
                    @readonly($used)
                >
                @error('satuan')
                    <small class="master-form-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="kpi-field">
                <label for="master-weight">Bobot (%)</label>
                <input
                    id="master-weight"
                    name="bobot"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    value="{{ $used ? $record->bobot : old('bobot', $record->bobot) }}"
                    required
                    @readonly($used)
                >
                @error('bobot')
                    <small class="master-form-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="kpi-field">
                <label for="master-polarity">Polaritas</label>

                @if ($used)
                    <input
                        id="master-polarity"
                        type="text"
                        value="{{ $record->polaritas ?: 'Belum ditetapkan' }}"
                        readonly
                    >
                    <input
                        type="hidden"
                        name="polaritas"
                        value="{{ $record->polaritas }}"
                    >
                @else
                    <select id="master-polarity" name="polaritas">
                        <option value="">Belum ditetapkan</option>
                        <option
                            value="positif"
                            @selected(old('polaritas', $record->polaritas) === 'positif')
                        >
                            Positif
                        </option>
                        <option
                            value="negatif"
                            @selected(old('polaritas', $record->polaritas) === 'negatif')
                        >
                            Negatif
                        </option>
                    </select>
                @endif

                @error('polaritas')
                    <small class="master-form-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="kpi-field">
                <label for="master-record-status">Status</label>
                <select id="master-record-status" name="status" required>
                    <option
                        value="aktif"
                        @selected(old('status', $record->status) === 'aktif')
                    >
                        Aktif
                    </option>
                    <option
                        value="nonaktif"
                        @selected(old('status', $record->status) === 'nonaktif')
                    >
                        Nonaktif
                    </option>
                </select>

                @error('status')
                    <small class="master-form-error">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <p class="master-form-note">
            Status nonaktif menyembunyikan KPI dari daftar master aktif
            dan pencocokan impor. Data target serta realisasi yang sudah
            tersimpan tidak dihapus.
        </p>

        <div class="realisasi-edit-actions">
            <a
                href="{{ route('master-kpi.index') }}"
                class="realisasi-edit-button"
            >
                Batal
            </a>

            <button
                type="submit"
                class="realisasi-edit-button is-primary"
                @disabled($errors->has('version'))
            >
                Simpan Master KPI
            </button>
        </div>
    </form>
</section>
@endsection