@extends('layouts.app')

@section('title', 'Impor Target KPI')
@section('navbar-title', 'Impor Target KPI')

@section('content')
    <section class="card target-import-card">
        <div class="target-import-header">
            <h2>Impor Target KPI</h2>
            <p>
                Pilih ULP tujuan dan tahun, kemudian unggah file target KPI.
            </p>
        </div>


        @if ($ulps->isEmpty())
            <div class="target-import-alert target-import-alert-error" role="alert">
                Belum ada ULP aktif. Impor tidak dapat dilakukan.
            </div>
        @endif

        <form
            action="{{ route('target-kpi.import') }}"
            method="POST"
            enctype="multipart/form-data"
        >
            @csrf

            <div class="target-import-field">
                <label for="target-import-ulp">ULP Tujuan</label>

                <select
                    id="target-import-ulp"
                    name="id_ulp"
                    required
                    @error('id_ulp') aria-invalid="true" @enderror
                >
                    <option value="">Pilih ULP tujuan</option>

                    @foreach ($ulps as $ulp)
                        <option
                            value="{{ $ulp->id_ulp }}"
                            @selected(
                                (string) old('id_ulp', request()->query('id_ulp'))
                                === (string) $ulp->id_ulp
                            )
                        >
                            {{ $ulp->nama_ulp }}
                        </option>
                    @endforeach
                </select>

                <small>
                    Data target akan disimpan untuk ULP yang dipilih.
                </small>
            </div>

            <div class="target-import-field">
                <label for="target-import-year">Tahun Target</label>

                <input
                    id="target-import-year"
                    name="tahun"
                    type="number"
                    min="2000"
                    max="2100"
                    step="1"
                    value="{{ old('tahun', request()->query('tahun', 2026)) }}"
                    required
                    aria-describedby="target-import-year-help"
                    @error('tahun') aria-invalid="true" @enderror
                >

                <small id="target-import-year-help">
                    Tahun harus sesuai dengan judul kolom pada file.
                    Contoh: TARGET 2026 menggunakan tahun 2026.
                </small>
            </div>

            <div class="target-import-field">
                <label for="target-import-file">File Target KPI</label>

                <input
                    id="target-import-file"
                    name="file"
                    type="file"
                    accept=".xlsx,.xls,.csv"
                    required
                    aria-describedby="target-import-file-help"
                    @error('file') aria-invalid="true" @enderror
                >

                <small id="target-import-file-help">
                    Format: .xlsx, .xls, atau .csv. Maksimal 10 MB.
                    Jika impor gagal, pilih kembali file sebelum mengirim.
                </small>
            </div>

            <div class="target-import-note">
                <strong>Perhatikan sebelum mengimpor</strong>
                <p>
                    Untuk ULP, KPI, dan tahun yang sama, impor akan memperbarui
                    target tahunan serta target bulanan yang diproses dari file.
                    Nilai bulanan kosong akan menghapus target pada bulan tersebut.
                    Bulan yang tidak diproses oleh importer tetap dipertahankan.
                    Target dan hasil perhitungan realisasi terkait juga diperbarui
                    menggunakan rumus sistem.
            </p>
            </div>

            <div class="target-import-actions">
                <a
                    href="{{ route('target.kpi') }}"
                    class="target-import-cancel"
                >
                    Kembali
                </a>

                <button
                    type="submit"
                    class="target-import-submit"
                    @disabled($ulps->isEmpty())
                >
                    Impor dan Simpan
                </button>
            </div>
        </form>
    </section>
@endsection