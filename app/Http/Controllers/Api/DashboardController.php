<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterKpi;
use App\Models\RealisasiKpi;
use App\Services\KpiCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /**
     * Struktur KPI induk dan komponennya.
     */
    private array $kelompokKpi = [
        3  => [4, 5, 6],
        7  => [8, 9],
        10 => [11, 14, 15, 16],
        17 => [18, 19, 20, 21],
        24 => [25, 26],
        27 => [28, 29],
        30 => [31, 32, 33],
        34 => [35, 36, 37],
        38 => [39, 40],
        41 => [42, 43, 44],
    ];

    /**
     * Mengambil dashboard dan menghitung ulang hasil KPI.
     */
    public function index(
        Request $request,
        KpiCalculationService $kalkulasi
    ) {
        $request->validate([
            'id_ulp' => 'nullable|integer|min:1',
            'id_periode' => 'nullable|integer|min:1',
            'tahun' => 'nullable|integer|min:2020|max:2100',
        ]);

        $query = RealisasiKpi::query()->with([
            'ulp:id_ulp,nama_ulp',
            'kpi:id_kpi,kode_kpi,nama_kpi,bobot,polaritas',
            'periode',
        ]);

        if ($request->filled('id_ulp')) {
            $query->where('id_ulp', $request->id_ulp);
        }

        if ($request->filled('id_periode')) {
            $query->where('id_periode', $request->id_periode);
        }

        if ($request->filled('tahun')) {
            $query->whereHas('periode', function ($q) use ($request) {
                $q->where('tahun', $request->tahun);
            });
        }

        $data = $query
            ->orderBy('id_ulp')
            ->orderBy('id_kpi')
            ->orderBy('nama_pendukung')
            ->get();

        /*
         * Hitung hasil individual.
         * Hasil hanya dihitung jika target dan realisasi tersedia.
         */
        foreach ($data as $item) {
            $hasil = $kalkulasi->hitung($item);

            $item->setAttribute(
                'pencapaian_hasil',
                $hasil['pencapaian'] ?? null
            );

            $item->setAttribute(
                'nilai_kpi_hasil',
                $hasil['nilai_kpi'] ?? null
            );
        }

        /*
         * Ringkas setiap KPI berdasarkan ID.
         */
        $hasilPerKpi = [];

        foreach ($data as $item) {
            $id = (int) $item->id_kpi;

            if (!isset($hasilPerKpi[$id])) {
                $hasilPerKpi[$id] = [
                    'id_kpi' => $id,
                    'kode_kpi' => $item->kpi->kode_kpi ?? null,
                    'nama_kpi' => $item->kpi->nama_kpi ?? null,
                    'bobot' => (float) ($item->kpi->bobot ?? 0),
                    'jumlah_baris' => 0,
                    'jumlah_dihitung' => 0,
                    'nilai_kpi' => 0.0,
                    'nilai_lengkap' => true,
                ];
            }

            $hasilPerKpi[$id]['jumlah_baris']++;

            if (
                $item->pencapaian_hasil !== null
                && $item->nilai_kpi_hasil !== null
            ) {
                $hasilPerKpi[$id]['jumlah_dihitung']++;
                $hasilPerKpi[$id]['nilai_kpi'] +=
                    (float) $item->nilai_kpi_hasil;
            } else {
                $hasilPerKpi[$id]['nilai_lengkap'] = false;
            }
        }

        /*
         * Hitung KPI gabungan.
         *
         * KPI020 (ID 11):
         * gabungan KPI021 (ID 12) dan KPI022 (ID 13).
         *
         * KPI030 (ID 21):
         * gabungan KPI031 (ID 22) dan KPI032 (ID 23).
         *
         * Komponen gabungan harus memiliki data lengkap.
         * Nilai anak dihitung berdasarkan bobot masing-masing.
         */
        $hasilGabungan = [];

        $pasanganGabungan = [
            11 => [12, 13],
            21 => [22, 23],
        ];

        foreach ($pasanganGabungan as $idInduk => $idAnak) {
            $komponen = [];
            $lengkap = true;
            $nilai = 0.0;
            $totalBobot = 0.0;

            foreach ($idAnak as $id) {
                $anak = $hasilPerKpi[$id] ?? null;

                if ($anak === null || !$anak['nilai_lengkap']) {
                    $lengkap = false;
                    $komponen[] = [
                        'id_kpi' => $id,
                        'nilai_kpi' => null,
                        'status' => 'data_belum_lengkap',
                    ];
                    continue;
                }

                $komponen[] = [
                    'id_kpi' => $id,
                    'nilai_kpi' => round($anak['nilai_kpi'], 2),
                    'status' => 'dihitung',
                ];

                $nilai += $anak['nilai_kpi'];
                $totalBobot += $anak['bobot'];
            }

            $masterInduk = MasterKpi::find($idInduk);

            $hasilGabungan[$idInduk] = [
                'id_kpi' => $idInduk,
                'kode_kpi' => $masterInduk->kode_kpi ?? null,
                'nama_kpi' => $masterInduk->nama_kpi ?? null,
                'nilai_kpi' => (
                    $lengkap && $totalBobot > 0
                ) ? round($nilai, 2) : null,
                'nilai_lengkap' => $lengkap && $totalBobot > 0,
                'komponen' => $komponen,
            ];
        }

        /*
         * Susun kelompok KPI.
         */
        $ringkasanKelompok = [];

        foreach ($this->kelompokKpi as $idInduk => $anakIds) {
            $masterInduk = MasterKpi::find($idInduk);
            $rincian = [];
            $nilaiKelompok = 0.0;
            $lengkap = true;

            foreach ($anakIds as $idAnak) {
                /*
                 * KPI020 dan KPI030 menggunakan hasil gabungan,
                 * bukan menjumlahkan komponen pendukung dua kali.
                 */
                if (isset($hasilGabungan[$idAnak])) {
                    $anak = $hasilGabungan[$idAnak];

                    if (!$anak['nilai_lengkap']) {
                        $lengkap = false;
                    } else {
                        $nilaiKelompok += $anak['nilai_kpi'];
                    }

                    $rincian[] = [
                        'id_kpi' => $idAnak,
                        'kode_kpi' => $anak['kode_kpi'],
                        'nama_kpi' => $anak['nama_kpi'],
                        'nilai_kpi' => $anak['nilai_kpi'],
                        'status' => $anak['nilai_lengkap']
                            ? 'dihitung'
                            : 'data_belum_lengkap',
                        'komponen' => $anak['komponen'],
                    ];

                    continue;
                }

                $anak = $hasilPerKpi[$idAnak] ?? null;

                if ($anak === null || !$anak['nilai_lengkap']) {
                    $lengkap = false;

                    $rincian[] = [
                        'id_kpi' => $idAnak,
                        'nilai_kpi' => null,
                        'status' => 'data_belum_lengkap',
                    ];

                    continue;
                }

                $nilaiKelompok += $anak['nilai_kpi'];

                $rincian[] = [
                    'id_kpi' => $idAnak,
                    'kode_kpi' => $anak['kode_kpi'],
                    'nama_kpi' => $anak['nama_kpi'],
                    'nilai_kpi' => round($anak['nilai_kpi'], 2),
                    'status' => 'dihitung',
                ];
            }

            $ringkasanKelompok[] = [
                'id_kpi_induk' => $idInduk,
                'kode_kpi' => $masterInduk->kode_kpi ?? null,
                'nama_kpi' => $masterInduk->nama_kpi ?? null,
                'nilai_kpi_kelompok' => $lengkap
                    ? round($nilaiKelompok, 2)
                    : null,
                'status_perhitungan' => $lengkap
                    ? 'lengkap'
                    : 'data_belum_lengkap',
                'rincian_anak' => $rincian,
            ];
        }

        /*
         * Total NKO mengikuti komponen TOTAL pada template.
         * KPI12 tidak dimasukkan karena bobotnya nol
         * dan merupakan bagian kepatuhan/tata kelola.
         */
        $idKomponenNko = [
            1, 2, 3, 7, 10, 17, 24, 27, 30, 34, 38,
        ];

        $totalNko = 0.0;
        $nkoLengkap = true;
        $rincianNko = [];

        foreach ($idKomponenNko as $id) {
            if (isset($this->kelompokKpi[$id])) {
                $kelompok = collect($ringkasanKelompok)
                    ->firstWhere('id_kpi_induk', $id);

                $nilai = $kelompok['nilai_kpi_kelompok'] ?? null;
                $nama = $kelompok['nama_kpi'] ?? null;
            } else {
                $indikator = $hasilPerKpi[$id] ?? null;

                $nilai = (
                    $indikator !== null
                    && $indikator['nilai_lengkap']
                ) ? round($indikator['nilai_kpi'], 2) : null;

                $nama = $indikator['nama_kpi'] ?? null;
            }

            if ($nilai === null) {
                $nkoLengkap = false;
            } else {
                $totalNko += $nilai;
            }

            $rincianNko[] = [
                'id_kpi' => $id,
                'nama_kpi' => $nama,
                'nilai_kpi' => $nilai,
            ];
        }

        $nilaiTersedia = $data->filter(function ($item) {
            return $item->pencapaian_hasil !== null
                && $item->nilai_kpi_hasil !== null;
        });

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan Dashboard berhasil diambil',
            'filters' => [
                'id_ulp' => $request->id_ulp,
                'id_periode' => $request->id_periode,
                'tahun' => $request->tahun,
            ],
            'ringkasan' => [
                'total_data_realisasi' => $data->count(),
                'data_dengan_nilai' => $nilaiTersedia->count(),
                'data_belum_dihitung' =>
                    $data->count() - $nilaiTersedia->count(),
                'total_nilai_baris_individual' => round(
                    (float) $nilaiTersedia->sum('nilai_kpi_hasil'),
                    2
                ),
                'total_nko' => $nkoLengkap
                    ? round($totalNko, 2)
                    : null,
                'status_nko' => $nkoLengkap
                    ? 'lengkap'
                    : 'belum_lengkap',
                'rincian_nko' => $rincianNko,
            ],
            'kelompok_kpi' => $ringkasanKelompok,
            'kpi_gabungan' => array_values($hasilGabungan),
            'data' => $data,
        ]);
    }
}