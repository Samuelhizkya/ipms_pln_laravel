<?php

namespace App\Services;

use App\Models\MasterKpi;
use App\Models\RealisasiKpi;

class KpiCalculationService
{
    /**
     * Menghitung pencapaian berdasarkan rumus KPI.
     * Hasil dibatasi maksimal 110 sesuai template Excel.
     */
    public function hitungPencapaian(
        float $realisasi,
        float $target,
        string $polaritas,
        string $kodeKpi = ''
    ): float {
        $polaritas = strtolower(trim($polaritas));

        // KPI01: rumus khusus Penambahan Penjualan Tenaga Listrik.
        if ($kodeKpi === 'KPI01') {
            if ($target <= 0) {
                return 0.0;
            }

            $persentase = ($realisasi / $target) * 100;

            if ($persentase >= 110) {
                return 110.0;
            }

            if ($persentase >= 100) {
                return $this->roundDown($persentase);
            }

            return $this->roundDown(
                ($persentase * 40 / 100) + 60
            );
        }

        // KPI negatif: pencapaian turun apabila realisasi melebihi target.
        if ($polaritas === 'negatif') {
            if ($target <= 0) {
                return 0.0;
            }

            return $this->batasiPencapaian(
                (2 - ($realisasi / $target)) * 100
            );
        }

        // KPI pendukung yang polaritasnya belum diisi di master.
        $kodePositif = [
            'KPI021',
            'KPI022',
            'KPI031',
            'KPI032',
        ];

        if (
            $polaritas === '' &&
            in_array($kodeKpi, $kodePositif, true)
        ) {
            $polaritas = 'positif';
        }

        if ($polaritas !== 'positif') {
            return 0.0;
        }

        // Aturan khusus ketika target nol sesuai template.
        if ($target == 0.0) {
            if (in_array($kodeKpi, ['KPI024', 'KPI025'], true)) {
                return 100.0;
            }

            if ($kodeKpi === 'KPI041') {
                return 110.0;
            }

            if ($kodeKpi === 'KPI042') {
                return $realisasi == 0.0 ? 100.0 : 0.0;
            }

            return 0.0;
        }

        return $this->batasiPencapaian(
            ($realisasi / $target) * 100
        );
    }

    /**
     * Menghitung nilai KPI berdasarkan pencapaian dan bobot.
     */
    public function hitungNilaiKpi(
        float $pencapaian,
        float $bobot
    ): float {
        if ($pencapaian <= 0 || $bobot <= 0) {
            return 0.0;
        }

        if ($pencapaian >= 110) {
            return $this->roundDown(1.1 * $bobot);
        }

        return $this->roundDown(
            ($pencapaian * $bobot) / 100
        );
    }

    /**
     * Membatasi pencapaian antara 0 dan 110.
     */
    private function batasiPencapaian(float $nilai): float
    {
        if ($nilai >= 110) {
            return 110.0;
        }

        if ($nilai <= 0) {
            return 0.0;
        }

        return $this->roundDown($nilai);
    }

    /**
     * Membulatkan ke bawah dua angka desimal.
     */
    private function roundDown(
        float $nilai,
        int $desimal = 2
    ): float {
        $pengali = pow(10, $desimal);

        return floor(($nilai + 1e-10) * $pengali)
            / $pengali;
    }

    /**
     * Menghitung satu baris realisasi KPI.
     *
     * KPI031 disimpan dalam skala ribuan.
     * KPI032 disimpan dalam skala miliar.
     * Karena itu, realisasi dikonversi ke satuan target
     * sebelum dibandingkan.
     */
    public function hitung(RealisasiKpi $realisasiKpi): ?array
    {
        if (
            $realisasiKpi->target_periode === null ||
            $realisasiKpi->realisasi === null
        ) {
            return null;
        }

        $masterKpi = MasterKpi::find(
            $realisasiKpi->id_kpi
        );

        if (!$masterKpi) {
            return null;
        }

        $kodeKpi = strtoupper(
            trim((string) $masterKpi->kode_kpi)
        );

        $polaritas = strtolower(
            trim((string) $masterKpi->polaritas)
        );

        $kodePositif = [
            'KPI021',
            'KPI022',
            'KPI031',
            'KPI032',
        ];

        if (
            $polaritas === '' &&
            in_array($kodeKpi, $kodePositif, true)
        ) {
            $polaritas = 'positif';
        }

        if (
            $polaritas !== 'positif' &&
            $polaritas !== 'negatif'
        ) {
            return null;
        }

        $target = (float) $realisasiKpi->target_periode;
        $realisasi = (float) $realisasiKpi->realisasi;
        $bobot = (float) $masterKpi->bobot;

        // Samakan skala realisasi dengan satuan target.
        if ($kodeKpi === 'KPI031') {
            $realisasi *= 1000;
        } elseif ($kodeKpi === 'KPI032') {
            $realisasi *= 1000000000;
        }

        $pencapaian = $this->hitungPencapaian(
            $realisasi,
            $target,
            $polaritas,
            $kodeKpi
        );

        $nilaiKpi = $this->hitungNilaiKpi(
            $pencapaian,
            $bobot
        );

        return [
            'pencapaian' => $pencapaian,
            'nilai_kpi' => $nilaiKpi,
        ];
    }
}