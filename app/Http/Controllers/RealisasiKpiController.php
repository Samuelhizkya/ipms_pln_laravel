<?php

namespace App\Http\Controllers;

use App\Imports\RealisasiKpiImport;
use App\Models\Ulp;
use App\Models\PeriodeKinerja;
use App\Models\RealisasiKpi;
use App\Services\KpiCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class RealisasiKpiController extends Controller
{
    /**
     * Menampilkan halaman import Realisasi KPI.
     */
    public function create()
    {
        $ulps = Ulp::where('status', 'aktif')
            ->orderBy('nama_ulp')
            ->get();

        $periodes = PeriodeKinerja::where('tahun', 2026)
            ->orderBy('bulan')
            ->get();

        return view(
            'realisasi-kpi.import',
            compact('ulps', 'periodes')
        );
    }

    /**
     * Mengimpor data Realisasi KPI dari Excel.
     */
    public function import(
        Request $request,
        KpiCalculationService $kalkulasi
    ) {
        // 1. Validasi input.
        $request->validate([
            'id_ulp' => [
                'required',
                'integer',
                'exists:ulp,id_ulp',
            ],
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
        ]);

        // 2. Cegah impor ulang untuk ULP dan periode yang sama.
        $jumlahDataLama = RealisasiKpi::where(
            'id_ulp',
            $request->id_ulp
        )
            ->where('id_periode', $request->id_periode)
            ->count();

        if ($jumlahDataLama > 0) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Impor dibatalkan. ULP dan periode ini sudah memiliki '
                    . $jumlahDataLama
                    . ' data realisasi KPI. Periksa data yang ada '
                    . 'sebelum melakukan impor ulang.'
                );
        }

        // 3. Ambil informasi periode.
        $periode = PeriodeKinerja::findOrFail(
            $request->id_periode
        );

        // 4. Baca dan validasi isi Excel.
        $import = new RealisasiKpiImport();

        try {
            Excel::import(
                $import,
                $request->file('file')
            );
        } catch (\Throwable $e) {
            Log::error('Pembacaan Excel Realisasi KPI gagal.', [
                'pesan' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'File Excel gagal dibaca. Periksa format dan isi file.'
                );
        }

        $errors = $import->getErrors();
        $validRows = $import->getValidRows();

        // 5. Hentikan proses jika ditemukan kesalahan data.
        if (count($errors) > 0) {
            return back()
                ->withInput()
                ->with('errors_import', $errors)
                ->with('valid_rows', $validRows);
        }

        if (count($validRows) === 0) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Tidak ada data Realisasi KPI yang dapat disimpan.'
                );
        }

        // 6. Simpan realisasi dan hitung nilai KPI.
        try {
            $jumlahDisimpan = DB::transaction(
                function () use (
                    $validRows,
                    $request,
                    $periode,
                    $kalkulasi
                ) {
                    $jumlah = 0;

                    foreach ($validRows as $row) {
                        // Cari target tahunan berdasarkan ULP,
                        // KPI, dan tahun.
                        $target = DB::table('target_kpi')
                            ->where(
                                'id_ulp',
                                $request->id_ulp
                            )
                            ->where(
                                'id_kpi',
                                $row['id_kpi']
                            )
                            ->where(
                                'tahun',
                                $periode->tahun
                            )
                            ->first();

                        $targetTahunan = $target
                            ? $target->target_tahunan
                            : null;

                        // Cari target bulanan sesuai periode.
                        $targetPeriode = null;

                        if ($target) {
                            $targetPeriode = DB::table(
                                'target_kpi_bulanan'
                            )
                                ->where(
                                    'id_target',
                                    $target->id_target
                                )
                                ->where(
                                    'bulan',
                                    $periode->bulan
                                )
                                ->value('nilai_target');
                        }

                        // Susun data yang akan disimpan.
                        $data = [
                            'id_ulp' => $request->id_ulp,
                            'id_kpi' => $row['id_kpi'],
                            'id_pendukung' =>
                                $row['id_pendukung'] ?? null,
                            'nama_pendukung' =>
                                $row['nama_pendukung'] ?? null,
                            'satuan_pendukung' =>
                                $row['satuan_pendukung'] ?? null,
                            'id_periode' => $request->id_periode,
                            'target_tahunan' => $targetTahunan,
                            'target_periode' => $targetPeriode,
                            'realisasi' => $row['realisasi'],
                            'pencapaian' => null,
                            'nilai_kpi' => null,
                            'sumber_data' => 'Excel',
                            'status_data' => 'valid',
                        ];

                        // Hitung pencapaian dan nilai KPI.
                        $realisasiKpi = new RealisasiKpi($data);

                        $hasil = $kalkulasi->hitung(
                            $realisasiKpi
                        );

                        if ($hasil !== null) {
                            $data['pencapaian'] =
                                $hasil['pencapaian'];

                            $data['nilai_kpi'] =
                                $hasil['nilai_kpi'];
                        }

                        // Simpan ke database.
                        RealisasiKpi::create($data);

                        $jumlah++;
                    }

                    return $jumlah;
                }
            );
        } catch (\Throwable $e) {
            Log::error('Import Realisasi KPI gagal.', [
                'pesan' => $e->getMessage(),
                'ulp' => $request->id_ulp,
                'periode' => $request->id_periode,
            ]);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Data gagal disimpan. Periksa log Laravel '
                    . 'untuk mengetahui detail kesalahan.'
                );
        }

        // 7. Tampilkan pesan berhasil.
        return back()->with(
            'success',
            'Data Realisasi KPI berhasil disimpan. '
            . $jumlahDisimpan
            . ' baris diproses.'
        );
    }
}