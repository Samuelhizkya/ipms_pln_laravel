<?php

namespace App\Services;

use App\Models\PeriodeKinerja;
use App\Models\RealisasiKpi;
use App\Models\TargetKpi;
use Illuminate\Support\Facades\DB;
use LogicException;

class TargetRealisasiSyncService
{
    public function __construct(
        private KpiCalculationService $kalkulasi
    ) {
    }

    /**
     * Sinkronkan realisasi untuk ULP, KPI, dan tahun target.
     *
     * Harus dipanggil di dalam transaksi penyimpanan target
     * supaya seluruh perubahan dibatalkan jika terjadi kegagalan.
     */
    public function sync(TargetKpi $target): int
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException(
                'Sinkronisasi target harus dijalankan dalam transaksi.'
            );
        }

        $bulanPerPeriode = PeriodeKinerja::query()
            ->where('tahun', $target->tahun)
            ->pluck('bulan', 'id_periode');

        if ($bulanPerPeriode->isEmpty()) {
            return 0;
        }

        $targetBulanan = $target->targetBulanan()
            ->get(['bulan', 'nilai_target']);

        if (
            $targetBulanan->pluck('bulan')->unique()->count()
            !== $targetBulanan->count()
        ) {
            throw new LogicException(
                'Ditemukan target bulanan ganda untuk satu bulan.'
            );
        }

        $nilaiPerBulan = $targetBulanan->pluck(
            'nilai_target',
            'bulan'
        );

        $realisasiRows = RealisasiKpi::query()
            ->where('id_ulp', $target->id_ulp)
            ->where('id_kpi', $target->id_kpi)
            ->whereIn(
                'id_periode',
                $bulanPerPeriode->keys()->all()
            )
            ->orderBy('id_realisasi')
            ->lockForUpdate()
            ->get();

        foreach ($realisasiRows as $realisasi) {
            $bulan = (int) $bulanPerPeriode->get(
                $realisasi->id_periode
            );

            $realisasi->target_tahunan = $target->target_tahunan;

            // Target yang belum tersedia tetap null, bukan nol.
            $realisasi->target_periode = $nilaiPerBulan->get(
                $bulan
            );

            // Gunakan rumus backend yang sudah tersedia.
            $hasil = $this->kalkulasi->hitung($realisasi);

            $realisasi->pencapaian = $hasil['pencapaian'] ?? null;
            $realisasi->nilai_kpi = $hasil['nilai_kpi'] ?? null;

            $realisasi->save();
        }

        return $realisasiRows->count();
    }
}