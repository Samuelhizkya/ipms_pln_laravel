<?php

namespace App\Http\Controllers;

use App\Imports\RealisasiKpiImport;
use App\Models\PeriodeKinerja;
use App\Models\RealisasiKpi;
use App\Models\TargetKpi;
use App\Models\Ulp;
use App\Services\KpiCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class RealisasiKpiController extends Controller
{
    /**
     * Ambil ULP yang sudah diperiksa oleh middleware.
     */
    private function accountUnit(Request $request): Ulp
    {
        abort_unless(
            $request->session()->get('user.role') === 'ulp',
            403,
            'Impor realisasi hanya tersedia untuk akun ULP.'
        );

        $unit = $request->attributes->get('account_ulp');

        abort_unless(
            $unit instanceof Ulp
                && (int) $unit->id_ulp ===
                    (int) $request->session()->get('user.id_ulp'),
            403,
            'Identitas ULP tidak tersedia. Silakan login kembali.'
        );

        return $unit;
    }

    /**
     * Menampilkan form impor realisasi.
     */
    public function create(Request $request)
    {
        $this->accountUnit($request);

        // Hanya mengambil periode yang sudah ada di database.
        $periodes = PeriodeKinerja::query()
            ->whereBetween('bulan', [1, 12])
            ->orderByDesc('tahun')
            ->orderBy('bulan')
            ->get();

        return view(
            'realisasi-kpi.import',
            compact('periodes')
        );
    }

    /**
     * Membaca Excel, menyimpan realisasi, dan menghitung KPI.
     */
    public function import(
        Request $request,
        KpiCalculationService $kalkulasi
    ) {
        $accountUnit = $this->accountUnit($request);

        // ULP berasal dari akun, bukan dari pilihan pengguna.
        $unitId = (int) $accountUnit->id_ulp;

        $validated = $request->validate([
            'id_periode' => [
                'required',
                'integer',
                'exists:periode_kinerja,id_periode',
            ],
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ], [
            'id_periode.required' =>
                'Pilih periode realisasi terlebih dahulu.',
            'id_periode.integer' =>
                'Pilihan periode realisasi tidak valid.',
            'id_periode.exists' =>
                'Periode realisasi tidak tersedia.',
            'file.required' =>
                'Pilih file Excel realisasi terlebih dahulu.',
            'file.mimes' =>
                'Gunakan file Excel berformat .xlsx atau .xls.',
            'file.max' =>
                'Ukuran file maksimal 10 MB.',
        ]);

        $periodeId = (int) $validated['id_periode'];

        $oldInput = [
            'id_periode' => $periodeId,
        ];

        app(\App\Services\KpiImportFileValidator::class)->validate(
    $request->file('file'),
    'realisasi'
);

        $import = new RealisasiKpiImport();

        try {
            Excel::import(
                $import,
                $request->file('file')
            );
        } catch (Throwable $e) {
            Log::error('Pembacaan Excel Realisasi KPI gagal.', [
                'id_ulp' => $unitId,
                'id_periode' => $periodeId,
                'exception' => $e,
            ]);

            return back()
                ->withInput($oldInput)
                ->with(
                    'error',
                    'File Excel gagal dibaca. Periksa format dan isi file.'
                );
        }

        $errors = $import->getErrors();
        $validRows = $import->getValidRows();

        if (count($errors) > 0) {
            return back()
                ->withInput($oldInput)
                ->with('errors_import', $errors);
        }

        if (count($validRows) === 0) {
            return back()
                ->withInput($oldInput)
                ->with(
                    'error',
                    'Tidak ada data realisasi KPI yang dapat disimpan.'
                );
        }

        try {
            $result = DB::transaction(
                function () use (
                    $validRows,
                    $unitId,
                    $periodeId,
                    $kalkulasi
                ) {
                    /*
                     * Kunci ULP terlebih dahulu.
                     *
                     * Controller impor target pada tahap sebelumnya
                     * menggunakan penguncian ULP yang sama.
                     */
                    $unit = Ulp::query()
                        ->where('id_ulp', $unitId)
                        ->where('status', 'aktif')
                        ->lockForUpdate()
                        ->first();

                    if (!$unit) {
                        throw ValidationException::withMessages([
                            'id_ulp' =>
                                'ULP akun tidak tersedia atau tidak aktif.',
                        ]);
                    }

                    $periode = PeriodeKinerja::query()
                        ->where('id_periode', $periodeId)
                        ->sharedLock()
                        ->first();

                    if (
                        !$periode ||
                        (int) $periode->bulan < 1 ||
                        (int) $periode->bulan > 12
                    ) {
                        throw ValidationException::withMessages([
                            'id_periode' =>
                                'Periode realisasi tidak tersedia atau tidak valid.',
                        ]);
                    }

                    /*
                     * Pemeriksaan dilakukan setelah ULP terkunci.
                     *
                     * Pengiriman kedua melalui alur ini menunggu
                     * transaksi pertama selesai sebelum memeriksa.
                     */
                    $existing = RealisasiKpi::query()
                        ->where('id_ulp', $unitId)
                        ->where('id_periode', $periodeId)
                        ->lockForUpdate()
                        ->first(['id_realisasi']);

                    if ($existing) {
                        throw ValidationException::withMessages([
                            'id_periode' =>
                                'Impor dibatalkan. ULP dan periode ini '
                                . 'sudah memiliki data realisasi. '
                                . 'Periksa data pada tabel terlebih dahulu.',
                        ]);
                    }

                    $jumlah = 0;

                    foreach ($validRows as $row) {
                        $targets = TargetKpi::query()
                            ->where('id_ulp', $unitId)
                            ->where('id_kpi', $row['id_kpi'])
                            ->where('tahun', $periode->tahun)
                            ->lockForUpdate()
                            ->get();

                        if ($targets->count() > 1) {
                            throw new RuntimeException(
                                'Ditemukan target ganda untuk ULP, KPI, '
                                . 'dan tahun yang sama.'
                            );
                        }

                        $target = $targets->first();

                        $targetTahunan = $target
                            ? $target->target_tahunan
                            : null;

                        $targetPeriode = null;

                        if ($target) {
                            $bulanan = $target->targetBulanan()
                                ->where(
                                    'bulan',
                                    (int) $periode->bulan
                                )
                                ->get();

                            if ($bulanan->count() > 1) {
                                throw new RuntimeException(
                                    'Ditemukan target bulanan ganda.'
                                );
                            }

                            $targetPeriode = $bulanan->first()
                                ?->nilai_target;
                        }

                        $realisasi = new RealisasiKpi([
                            'id_ulp' => $unitId,
                            'id_kpi' => $row['id_kpi'],
                            'id_pendukung' =>
                                $row['id_pendukung'] ?? null,
                            'nama_pendukung' =>
                                $row['nama_pendukung'] ?? null,
                            'satuan_pendukung' =>
                                $row['satuan_pendukung'] ?? null,
                            'id_periode' => $periodeId,
                            'target_tahunan' => $targetTahunan,
                            'target_periode' => $targetPeriode,
                            'realisasi' => $row['realisasi'],
                            'pencapaian' => null,
                            'nilai_kpi' => null,
                            'sumber_data' => 'Excel',
                            'status_data' => 'valid',
                        ]);

                        $hasil = $kalkulasi->hitung($realisasi);

                        $realisasi->pencapaian =
                            $hasil['pencapaian'] ?? null;

                        $realisasi->nilai_kpi =
                            $hasil['nilai_kpi'] ?? null;

                        $realisasi->save();

                        $jumlah++;
                    }

                    return [
                        'jumlah' => $jumlah,
                        'tahun' => (int) $periode->tahun,
                        'bulan' => (int) $periode->bulan,
                    ];
                },
                3
            );
        } catch (ValidationException $e) {
            return back()
                ->withInput($oldInput)
                ->withErrors($e->errors());
        } catch (Throwable $e) {
            Log::error('Penyimpanan Realisasi KPI gagal.', [
                'id_ulp' => $unitId,
                'id_periode' => $periodeId,
                'exception' => $e,
            ]);

            return back()
                ->withInput($oldInput)
                ->with(
                    'error',
                    'Impor realisasi dibatalkan. Tidak ada perubahan '
                    . 'dari impor ini yang disimpan. Periksa log '
                    . 'Laravel untuk mengetahui penyebabnya.'
                );
        }
    $warnings = $import->getWarnings();

    if ($warnings !== []) {
    $request->session()->flash(
        'warning',
        "Sebagian indikator tidak diimpor:\n"
        . implode("\n", $warnings)
    );
}
        return redirect()
            ->route('realisasi.kpi', [
                'id_ulp' => $unitId,
                'id_periode' => $periodeId,
                'tahun' => $result['tahun'],
                'bulan' => $result['bulan'],
            ])
            ->with(
                'success',
                'Data realisasi KPI berhasil disimpan. '
                . $result['jumlah']
                . ' baris diproses.'
            );
    }
}