<?php

namespace App\Http\Controllers;

use App\Models\RealisasiKpi;
use App\Models\TargetKpi;
use App\Models\Ulp;
use App\Support\WebLogin;
use Illuminate\Http\Request;

class UlpDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = WebLogin::resolve($request);

        abort_unless(
            $user && $user->role === 'ulp',
            403,
            'Halaman ini khusus akun ULP.'
        );

        abort_unless(
            $user->id_ulp,
            403,
            'Akun belum memiliki ULP. Hubungi Admin.'
        );

        // Identitas ULP berasal dari akun yang masuk.
        $ulp = Ulp::query()
            ->where('id_ulp', $user->id_ulp)
            ->where('status', 'aktif')
            ->firstOrFail();

        $filters = $request->validate([
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $latest = RealisasiKpi::query()
            ->join(
                'periode_kinerja',
                'realisasi_kpi.id_periode',
                '=',
                'periode_kinerja.id_periode'
            )
            ->where('realisasi_kpi.id_ulp', $ulp->id_ulp)
            ->orderByDesc('periode_kinerja.tahun')
            ->orderByDesc('periode_kinerja.bulan')
            ->first([
                'periode_kinerja.tahun',
                'periode_kinerja.bulan',
            ]);

        $targetYears = TargetKpi::query()
            ->where('id_ulp', $ulp->id_ulp)
            ->distinct()
            ->pluck('tahun');

        $actualYears = RealisasiKpi::query()
            ->join(
                'periode_kinerja',
                'realisasi_kpi.id_periode',
                '=',
                'periode_kinerja.id_periode'
            )
            ->where('realisasi_kpi.id_ulp', $ulp->id_ulp)
            ->distinct()
            ->pluck('periode_kinerja.tahun');

        $defaultYear = $latest?->tahun
            ?? $targetYears->max()
            ?? now()->year;

        $year = (int) ($filters['tahun'] ?? $defaultYear);
        $month = (int) ($filters['bulan'] ?? $latest?->bulan ?? 1);

        $years = $targetYears
            ->merge($actualYears)
            ->push($year)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->sortDesc()
            ->values();

        // Target tahunan milik unit akun.
        $targets = TargetKpi::query()
            ->with(['kpi', 'targetBulanan'])
            ->where('id_ulp', $ulp->id_ulp)
            ->where('tahun', $year)
            ->orderBy('id_kpi')
            ->get();

        // Realisasi pada bulan dan tahun terpilih.
        $actuals = RealisasiKpi::query()
            ->with(['kpi', 'periode'])
            ->where('id_ulp', $ulp->id_ulp)
            ->whereHas('periode', function ($query) use ($year, $month) {
                $query->where('tahun', $year)
                    ->where('bulan', $month);
            })
            ->orderBy('id_kpi')
            ->orderBy('nama_pendukung')
            ->get();

        $assessed = $actuals->filter(
            fn ($row) => $row->pencapaian !== null
        );

        $counts = [
            'achieved' => $assessed->filter(
                fn ($row) => (float) $row->pencapaian >= 100
            )->count(),

            'warning' => $assessed->filter(
                fn ($row) =>
                    (float) $row->pencapaian >= 90 &&
                    (float) $row->pencapaian < 100
            )->count(),

            'danger' => $assessed->filter(
                fn ($row) => (float) $row->pencapaian < 90
            )->count(),

            'missing' => $actuals->count() - $assessed->count(),
        ];

        // Data setahun untuk grafik tren unit akun.
        $yearlyActuals = RealisasiKpi::query()
            ->with(['kpi', 'periode'])
            ->where('id_ulp', $ulp->id_ulp)
            ->whereHas('periode', function ($query) use ($year) {
                $query->where('tahun', $year);
            })
            ->orderBy('id_kpi')
            ->orderBy('nama_pendukung')
            ->get();

$dashboardUlps = collect();

// Panel ranking hanya diperlukan pada halaman dashboard.
if ($request->routeIs('ulp.dashboard')) {
    $rankingRequest = Request::create('/', 'GET', [
        'tahun' => $year,
        'bulan' => $month,
    ]);

    $rankingView = app(
        \App\Http\Controllers\RankingController::class
    )->index($rankingRequest);

    $rankingData = $rankingView->getData();

    $dashboardUlps = collect($rankingData['dataRanking'])
        ->map(fn ($item) => (object) $item)
        ->values();
}
// Ambil NKO untuk unit akun dan periode terpilih.
$periodeNko = \App\Models\PeriodeKinerja::query()
    ->where('tahun', $year)
    ->where('bulan', $month)
    ->first();

$nkoSummary = [
    'total_nko' => null,
    'status_nko' => 'periode_belum_tersedia',
    'rincian_nko' => [],
];

if ($periodeNko) {
    $nkoRequest = Request::create('/', 'GET', [
        'id_ulp' => (int) $ulp->id_ulp,
        'id_periode' => (int) $periodeNko->id_periode,
        'tahun' => $year,
    ]);

    $nkoResponse = app(
        \App\Http\Controllers\Api\DashboardController::class
    )->index(
        $nkoRequest,
        app(\App\Services\KpiCalculationService::class)
    );

    $nkoSummary = $nkoResponse->getData(true)['ringkasan'];
}
        $masterKpiCount = \App\Models\MasterKpi::where('status', 'aktif')->count();

        return view('ulp.dashboard', compact(
            'ulp',
            'year',
            'month',
            'years',
            'targets',
            'actuals',
            'counts',
            'yearlyActuals',
            'dashboardUlps',
            'nkoSummary',
            'masterKpiCount'
        ));
    }
}