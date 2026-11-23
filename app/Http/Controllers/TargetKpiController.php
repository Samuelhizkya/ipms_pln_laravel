<?php

namespace App\Http\Controllers;

use App\Imports\TargetKpiImport;
use App\Models\TargetKpi;
use App\Models\Ulp;
use App\Services\TargetRealisasiSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class TargetKpiController extends Controller
{
    public function create()
    {
        $ulps = Ulp::query()
            ->where('status', 'aktif')
            ->orderBy('nama_ulp')
            ->get();

        return view('target-kpi.import', compact('ulps'));
    }

    public function import(
        Request $request,
        TargetRealisasiSyncService $sinkronisasi
    ) {
        $validated = $request->validate([
            'id_ulp' => [
                'required',
                'integer',
                Rule::exists('ulp', 'id_ulp')
                    ->where('status', 'aktif'),
            ],
            'tahun' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ], [
            'id_ulp.required' => 'Pilih ULP tujuan terlebih dahulu.',
            'id_ulp.exists' => 'ULP tujuan tidak tersedia atau tidak aktif.',
            'tahun.required' => 'Isi tahun target terlebih dahulu.',
            'tahun.integer' => 'Tahun target harus berupa bilangan bulat.',
            'tahun.min' => 'Tahun target minimal 2000.',
            'tahun.max' => 'Tahun target maksimal 2100.',
            'file.required' => 'Pilih file target terlebih dahulu.',
            'file.mimes' => 'Gunakan file .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $unitId = (int) $validated['id_ulp'];
        $tahun = (int) $validated['tahun'];

        // Hanya kembalikan pilihan form, bukan isi unggahan.
        $oldInput = [
            'id_ulp' => $unitId,
            'tahun' => $tahun,
        ];
        
app(\App\Services\KpiImportFileValidator::class)->validate(
    $request->file('file'),
    'target',
    $tahun
);
        $import = new TargetKpiImport($unitId, $tahun);
        
        try {
            Excel::import(
                $import,
                $request->file('file')
            );
        } catch (Throwable $e) {
            Log::error('Pembacaan file Target KPI gagal.', [
                'id_ulp' => $unitId,
                'tahun' => $tahun,
                'exception' => $e,
            ]);

            return back()
                ->withInput($oldInput)
                ->with(
                    'error',
                    'File target gagal dibaca. Periksa format dan isi file.'
                );
        }

        $errors = $import->getErrors();

        if (count($errors) > 0) {
            return back()
                ->withInput($oldInput)
                ->with('import_errors', $errors);
        }

        $rows = $import->getValidRows();

        if (count($rows) === 0) {
            return back()
                ->withInput($oldInput)
                ->with('import_errors', [
                    'Tidak ada data target KPI yang dapat diimpor.',
                ]);
        }

        try {
            $jumlahRealisasi = DB::transaction(
                function () use (
                    $rows,
                    $unitId,
                    $tahun,
                    $sinkronisasi
                ) {
                    // Serialisasi impor target untuk ULP yang sama.
                    $unit = Ulp::query()
                        ->where('id_ulp', $unitId)
                        ->where('status', 'aktif')
                        ->lockForUpdate()
                        ->first();

                    if (!$unit) {
                        throw new RuntimeException(
                            'ULP tujuan sudah tidak aktif atau tidak tersedia.'
                        );
                    }

                    $jumlahDisinkronkan = 0;
                    $processedKpis = [];

                    foreach ($rows as $row) {
                        $kpiId = (int) $row['id_kpi'];

                        if (isset($processedKpis[$kpiId])) {
                            throw new RuntimeException(
                                'File menghasilkan lebih dari satu target '
                                . 'untuk KPI yang sama.'
                            );
                        }

                        $processedKpis[$kpiId] = true;

                        $matches = TargetKpi::query()
                            ->where('id_ulp', $unitId)
                            ->where('id_kpi', $kpiId)
                            ->where('tahun', $tahun)
                            ->lockForUpdate()
                            ->get();

                        if ($matches->count() > 1) {
                            throw new RuntimeException(
                                'Ditemukan target ganda untuk ULP, KPI, '
                                . 'dan tahun yang sama.'
                            );
                        }

                        $target = $matches->first();

                        if (!$target) {
                            $target = new TargetKpi();

                            $target->id_ulp = $unitId;
                            $target->id_kpi = $kpiId;
                            $target->tahun = $tahun;
                        }

                        $target->target_tahunan =
                            $row['target_tahunan'];

                        $target->save();

                        /*
                         * Ganti hanya bulan yang dihasilkan importer.
                         *
                         * Bulan yang tidak dipetakan oleh importer
                         * tidak dihapus secara diam-diam.
                         */
                        foreach ($row['bulanan'] as $bulan => $nilai) {
                            $bulan = (int) $bulan;

                            if ($bulan < 1 || $bulan > 12) {
                                throw new RuntimeException(
                                    'Nomor bulan target tidak valid.'
                                );
                            }

                            $target->targetBulanan()
                                ->where('bulan', $bulan)
                                ->delete();

                            if ($nilai === null || $nilai === '') {
                                continue;
                            }

                            $target->targetBulanan()->create([
                                'bulan' => $bulan,
                                'nilai_target' => $nilai,
                            ]);
                        }

                        // Baca ulang nilai sesuai hasil penyimpanan DB.
                        $target->refresh();

                        $jumlahDisinkronkan += $sinkronisasi->sync(
                            $target
                        );
                    }

                    return $jumlahDisinkronkan;
                },
                3
            );
        } catch (Throwable $e) {
            Log::error('Penyimpanan Target KPI gagal.', [
                'id_ulp' => $unitId,
                'tahun' => $tahun,
                'exception' => $e,
            ]);

            return back()
                ->withInput($oldInput)
                ->with(
                    'error',
                    'Impor target dibatalkan. Tidak ada perubahan dari '
                    . 'impor ini yang disimpan. Periksa log Laravel '
                    . 'untuk mengetahui penyebabnya.'
                );
        }

        return redirect()
            ->route('target.kpi', [
                'id_ulp' => $unitId,
                'tahun' => $tahun,
            ])
            ->with(
                'success',
                'Target KPI berhasil diimpor. '
                . count($rows)
                . ' KPI diproses dan '
                . $jumlahRealisasi
                . ' baris realisasi terkait diperbarui.'
            );
    }
}