<?php

namespace App\Imports;

use App\Models\MasterKpi;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TargetKpiImport implements ToCollection, WithHeadingRow
{
    protected int $idUlp;
    protected int $tahun;

    protected array $errors = [];
    protected array $validRows = [];

    public function __construct(int $idUlp, int $tahun)
    {
        $this->idUlp = $idUlp;
        $this->tahun = $tahun;
    }

    public function collection(Collection $rows)
    {
        $masterKpis = MasterKpi::where('status', 'aktif')->get();

        $mapping = [
            'Penjualan Tenaga Listrik'
                => 'Penambahan Penjualan Tenaga Listrik',

            '-Penambahan Pelanggan'
                => '-Jumlah Penambahan Pelanggan',

            '-Penambahan Daya Tersambung'
                => '-Penambahan Daya Tersambung',

            'a. Feedback Rating Negatif pada PLN Mobile - Gangguan'
                => 'a. Feedback Rating Negatif pada PLN Mobile -Gangguan',

            'a. Kerusakan Peralatan Distribusi (sesuai kewenangan)'
                => 'b. Kerusakan Peralatan Distribusi (sesuaikewenangan)',

            'b. Gangguan TM (sesuai kewenangan)'
                => 'a. Gangguan TM (sesuai kewenangan)',
        ];

        // Mengikuti template asli: Januari sampai November.
        $bulan = [
            1  => 'sd_jan',
            2  => 'sd_feb',
            3  => 'sd_mar',
            4  => 'sd_apr',
            5  => 'sd_may',
            6  => 'sd_jun',
            7  => 'sd_jul',
            8  => 'sd_aug',
            9  => 'sd_sep',
            10 => 'sd_oct',
            11 => 'sd_nov',
        ];

        $kpiMap = [];

        foreach ($masterKpis as $kpi) {
            $kpiMap[$this->normalize($kpi->nama_kpi)] = $kpi;
        }

        /*
         * Target KPI017 dihitung dari tiga komponen:
         * Penambahan JTM, Penambahan JTR, dan Penambahan Trafo.
         */
        $komponenKpi017 = [
            'penambahan jtm',
            'penambahan jtr',
            'penambahan trafo',
        ];

        $totalKpi017 = [
            'jumlah_komponen' => 0,
            'target_tahunan' => 0.0,
            'bulanan' => array_fill_keys(array_keys($bulan), 0.0),
            'bulan_terisi' => array_fill_keys(array_keys($bulan), false),
        ];

        foreach ($rows as $index => $row) {
            $barisExcel = $index + 2;
            $indikatorAsli = trim((string) ($row['indikator'] ?? ''));

            if ($indikatorAsli === '' || is_numeric($indikatorAsli)) {
                continue;
            }

            $indikator = $this->normalize($indikatorAsli);
            $targetMentah = $row['target_' . $this->tahun] ?? null;

            /*
             * Proses komponen KPI017.
             */
            if (in_array($indikator, $komponenKpi017, true)) {
                if ($targetMentah === null || trim((string) $targetMentah) === '') {
                    continue;
                }

                $targetTahunan = $this->parseTarget(
                    $targetMentah,
                    $indikatorAsli
                );

                if ($targetTahunan === null) {
                    $this->errors[] =
                        "Baris {$barisExcel}: TARGET {$this->tahun} untuk \"{$indikatorAsli}\" harus berupa angka.";

                    continue;
                }

                $nilaiBulananBaris = [];

                foreach ($bulan as $nomorBulan => $kolom) {
                    $nilaiMentah = $row[$kolom] ?? null;

                    if ($nilaiMentah === null || trim((string) $nilaiMentah) === '') {
                        $nilaiBulananBaris[$nomorBulan] = null;
                        continue;
                    }

                    $nilai = $this->parseTarget(
                        $nilaiMentah,
                        $indikatorAsli
                    );

                    if ($nilai === null) {
                        $this->errors[] =
                            "Baris {$barisExcel}: target bulan {$nomorBulan} untuk \"{$indikatorAsli}\" bukan angka yang valid.";

                        continue 2;
                    }

                    $nilaiBulananBaris[$nomorBulan] = $nilai;
                }

                $totalKpi017['jumlah_komponen']++;
                $totalKpi017['target_tahunan'] += $targetTahunan;

                foreach ($nilaiBulananBaris as $nomorBulan => $nilai) {
                    if ($nilai === null) {
                        continue;
                    }

                    $totalKpi017['bulanan'][$nomorBulan] += $nilai;
                    $totalKpi017['bulan_terisi'][$nomorBulan] = true;
                }

                continue;
            }

            /*
             * Abaikan baris yang memang tidak memiliki target tahunan.
             */
            if ($targetMentah === null || trim((string) $targetMentah) === '') {
                continue;
            }

            $targetTahunan = $this->parseTarget(
                $targetMentah,
                $indikatorAsli
            );

            if ($targetTahunan === null) {
                $this->errors[] =
                    "Baris {$barisExcel}: TARGET {$this->tahun} untuk \"{$indikatorAsli}\" harus berupa angka atau rentang 95-100 untuk indikator Pengendalian Penggunaan Anggaran Investasi sesuai RKAP.";

                continue;
            }

            /*
             * Cocokkan nama indikator Excel dengan master_kpi.
             */
            $namaMaster = $indikator;

            foreach ($mapping as $excelName => $masterName) {
                if ($this->normalize($excelName) === $indikator) {
                    $namaMaster = $this->normalize($masterName);
                    break;
                }
            }

            $kpi = $kpiMap[$namaMaster] ?? null;

            if (!$kpi) {
                continue;
            }

            $nilaiBulanan = [];

            foreach ($bulan as $nomorBulan => $kolom) {
                $nilaiMentah = $row[$kolom] ?? null;

                if ($nilaiMentah === null || trim((string) $nilaiMentah) === '') {
                    $nilaiBulanan[$nomorBulan] = null;
                    continue;
                }

                $nilai = $this->parseTarget(
                    $nilaiMentah,
                    $indikatorAsli
                );

                if ($nilai === null) {
                    $this->errors[] =
                        "Baris {$barisExcel}: target bulan {$nomorBulan} untuk \"{$indikatorAsli}\" bukan angka yang valid.";

                    continue 2;
                }

                $nilaiBulanan[$nomorBulan] = $nilai;
            }

            $this->validRows[] = [
                'id_kpi' => $kpi->id_kpi,
                'id_ulp' => $this->idUlp,
                'tahun' => $this->tahun,
                'target_tahunan' => $targetTahunan,
                'bulanan' => $nilaiBulanan,
            ];
        }

        /*
         * Simpan target gabungan KPI017 hanya jika ketiga komponen
         * tersedia. Jika ada komponen yang tidak ditemukan, jangan
         * menghasilkan target gabungan yang berpotensi tidak lengkap.
         */
        if ($totalKpi017['jumlah_komponen'] > 0) {
            if ($totalKpi017['jumlah_komponen'] !== 3) {
                $this->errors[] =
                    'Target KPI017 belum lengkap. Pastikan komponen Penambahan JTM, Penambahan JTR, dan Penambahan Trafo memiliki TARGET ' .
                    $this->tahun . ' pada template.';
            } else {
                $kpi017 = $masterKpis->first(
                    fn ($kpi) => strtoupper(trim((string) $kpi->kode_kpi)) === 'KPI017'
                );

                if (!$kpi017) {
                    $this->errors[] =
                        'KPI017 tidak ditemukan pada master_kpi. Target tidak dapat disimpan.';
                } else {
                    // Hindari target KPI017 ganda.
                    $this->validRows = array_values(array_filter(
                        $this->validRows,
                        fn ($item) =>
                            (int) $item['id_kpi'] !== (int) $kpi017->id_kpi
                    ));

                    $nilaiBulananKpi017 = [];

                    foreach ($bulan as $nomorBulan => $kolom) {
                        $nilaiBulananKpi017[$nomorBulan] =
                            $totalKpi017['bulan_terisi'][$nomorBulan]
                                ? $totalKpi017['bulanan'][$nomorBulan]
                                : null;
                    }

                    $this->validRows[] = [
                        'id_kpi' => $kpi017->id_kpi,
                        'id_ulp' => $this->idUlp,
                        'tahun' => $this->tahun,
                        'target_tahunan' => $totalKpi017['target_tahunan'],
                        'bulanan' => $nilaiBulananKpi017,
                    ];
                }
            }
        }
    }

    /**
     * Membaca target angka.
     *
     * Rentang 95-100 hanya diterima untuk indikator
     * Pengendalian Penggunaan Anggaran Investasi sesuai RKAP.
     * Nilai yang disimpan sebagai target numerik adalah 100.
     * Aturan penilaian khusus diterapkan di KpiCalculationService.
     */
    protected function parseTarget($value, string $indikator): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $nama = $this->normalize($indikator);
        $teks = trim((string) $value);

        $indikatorAnggaran =
            str_contains($nama, 'pengendalian penggunaan anggaran investasi')
            && str_contains($nama, 'sesuai rkap');

        if (
            $indikatorAnggaran
            && preg_match('/^95\s*-\s*100$/', $teks)
        ) {
            return 100.0;
        }

        // Mendukung angka desimal dengan koma.
        if (preg_match('/^-?\d+,\d+$/', $teks)) {
            $teks = str_replace(',', '.', $teks);
        }

        if (!is_numeric($teks)) {
            return null;
        }

        return (float) $teks;
    }

    protected function normalize($value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        return str_replace('- ', '-', $value);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getValidRows(): array
    {
        return $this->validRows;
    }
}