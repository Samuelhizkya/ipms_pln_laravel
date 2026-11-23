<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    public function index(Request $request)
    {
        // =========================================
        // 1. FILTER PERIODE
        // =========================================

        $bulan = (int) $request->query('bulan', 2);
        $tahun = (int) $request->query('tahun', 2026);

        if ($bulan < 1 || $bulan > 12) {
            $bulan = 2;
        }

        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = 2026;
        }

        $namaBulan = [
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

        // =========================================
        // 2. AMBIL PERIODE DARI DATABASE
        // =========================================

        $periode = DB::table('periode_kinerja')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        // =========================================
        // 3. AMBIL SELURUH ULP AKTIF
        // =========================================

        $ulps = DB::table('ulp')
            ->where('status', 'aktif')
            ->orderBy('nama_ulp')
            ->get();

        // =========================================
        // 4. AMBIL RINGKASAN REALISASI KPI
        // =========================================

        $realisasi = collect();

        if ($periode) {
            $realisasi = DB::table('realisasi_kpi')
                ->select(
                    'id_ulp',

                    DB::raw('COUNT(*) AS total_realisasi'),

                    DB::raw(
                        'COUNT(DISTINCT id_kpi) AS total_kpi'
                    ),

                    DB::raw(
                        "COUNT(DISTINCT CASE
                            WHEN status_data = 'valid'
                            THEN id_kpi END) AS kpi_valid"
                    ),

                    DB::raw(
                        "COUNT(DISTINCT CASE
                            WHEN status_data = 'draft'
                            THEN id_kpi END) AS kpi_draft"
                    ),

                    DB::raw(
                        "COUNT(DISTINCT CASE
                            WHEN status_data = 'error'
                            THEN id_kpi END) AS kpi_error"
                    )
                )
                ->where('id_periode', $periode->id_periode)
                ->groupBy('id_ulp')
                ->get()
                ->keyBy('id_ulp');
        }

        // =========================================
        // 5. GABUNGKAN DATA ULP DAN REALISASI
        // =========================================

       $dataRanking = $ulps->map(function ($ulp) use (
    $realisasi,
    $periode,
    $tahun
) {
    $data = $realisasi->get($ulp->id_ulp);

    $nkoSummary = [
        'total_nko' => null,
        'status_nko' => $periode
            ? 'belum_ada_data'
            : 'periode_belum_tersedia',
        'rincian_nko' => [],
    ];

    if ($periode && $data) {
        $nkoRequest = Request::create('/', 'GET', [
            'id_ulp' => (int) $ulp->id_ulp,
            'id_periode' => (int) $periode->id_periode,
            'tahun' => $tahun,
        ]);

        $response = app(
            \App\Http\Controllers\Api\DashboardController::class
        )->index(
            $nkoRequest,
            app(\App\Services\KpiCalculationService::class)
        );

        $nkoSummary = $response->getData(true)['ringkasan'];
    }

    return (object) [
        'id_ulp' => $ulp->id_ulp,
        'kode_ulp' => $ulp->kode_ulp,
        'nama_ulp' => $ulp->nama_ulp,

        'total_realisasi' => $data->total_realisasi ?? 0,
        'total_kpi' => $data->total_kpi ?? 0,
        'kpi_valid' => $data->kpi_valid ?? 0,
        'kpi_draft' => $data->kpi_draft ?? 0,
        'kpi_error' => $data->kpi_error ?? 0,

        // Hasil perhitungan backend yang sudah tersedia.
        'nko' => $nkoSummary['total_nko'],
        'status_nko' => $nkoSummary['status_nko'],
        'rincian_nko' => $nkoSummary['rincian_nko'],

        // Penentuan urutan peringkat dikerjakan setelah
        // sambungan nilai NKO dan tampilannya diperiksa.
        'ranking' => null,

        'status_penilaian' => !$periode
            ? 'Periode belum tersedia'
            : (!$data
                ? 'Belum ada data'
                : ($nkoSummary['total_nko'] !== null
                    ? 'NKO tersedia'
                    : 'Data NKO belum lengkap')),
    ];
});
// Urutkan hanya ULP dengan NKO lengkap.
$bernilai = $dataRanking
    ->filter(fn ($item) =>
        $item->status_nko === 'lengkap'
        && $item->nko !== null
        && is_numeric($item->nko)
        && is_finite((float) $item->nko)
    )
    ->sort(function ($a, $b) {
        return ((float) $b->nko <=> (float) $a->nko)
            ?: strcmp($a->nama_ulp, $b->nama_ulp);
    })
    ->values();

$nilaiSebelumnya = null;
$peringkat = null;

foreach ($bernilai as $index => $item) {
    $nilai = number_format((float) $item->nko, 2, '.', '');

    if ($nilai !== $nilaiSebelumnya) {
        $peringkat = $index + 1;
    }

    $item->ranking = $peringkat;
    $nilaiSebelumnya = $nilai;
}

$tanpaNilai = $dataRanking
    ->filter(fn ($item) => $item->ranking === null)
    ->sortBy('nama_ulp')
    ->values();

$dataRanking = $bernilai->concat($tanpaNilai)->values();
        // =========================================
        // 6. RINGKASAN DASHBOARD
        // =========================================

        $ringkasan = [
            'jumlah_ulp' => $ulps->count(),

            'ulp_memiliki_data' => $dataRanking
                ->where('total_realisasi', '>', 0)
                ->count(),

            'total_realisasi' => $dataRanking
                ->sum('total_realisasi'),

            'total_kpi_valid' => $dataRanking
                ->sum('kpi_valid'),

            'total_kpi_draft' => $dataRanking
                ->sum('kpi_draft'),

            'total_kpi_error' => $dataRanking
                ->sum('kpi_error'),
        ];

        // =========================================
        // 7. STATUS PERIODE
        // =========================================

        $statusPeriode = $periode
            ? ucfirst($periode->status)
            : 'Periode belum tersedia';

        // =========================================
        // 8. KIRIM DATA KE HALAMAN RANKING
        // =========================================
if ($request->query('dashboard_nko') === '1') {
    return response()->json([
        'success' => true,
        'data' => $dataRanking->values(),
        'status_periode' => $statusPeriode,
    ]);
}
        return view('ranking.index', [
            'dataRanking' => $dataRanking,
            'ringkasan' => $ringkasan,
            'periode' => $periode,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'namaBulan' => $namaBulan[$bulan],
            'statusPeriode' => $statusPeriode,
        ]);
    }
}