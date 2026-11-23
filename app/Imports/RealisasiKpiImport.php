<?php

namespace App\Imports;

use App\Models\MasterKpi;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RealisasiKpiImport implements ToCollection, WithHeadingRow
{
    protected array $errors = [];

    protected array $validRows = [];
    protected array $warnings = [];
    /**
     * Header Excel berada pada baris ke-3.
     */
    public function headingRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows)
{
    if ($rows->isEmpty()) {
        $this->errors[] =
            'File tidak memiliki baris realisasi yang dapat dibaca.';

        return;
    }

    $firstRow = $rows->first();

    $headers = $firstRow instanceof Collection
        ? $firstRow->keys()->all()
        : array_keys((array) $firstRow);

    $missing = array_values(array_diff(
        ['indikator', 'pendukung', 'realisasi'],
        $headers
    ));

    $hasSupportingUnit =
        in_array('satuan_pendukung', $headers, true)
        || in_array('atuan_pendukung', $headers, true);

    if (!$hasSupportingUnit) {
        $missing[] = 'satuan_pendukung';
    }

    if ($missing !== []) {
        $this->errors[] =
            'Header pada baris 3 tidak sesuai template. '
            . 'Kolom yang belum terbaca: '
            . implode(', ', $missing)
            . '.';

        return;
    }

    $masterKpis = MasterKpi::where('status', 'aktif')->get();

        /*
        |--------------------------------------------------------------------------
        | Mapping khusus untuk indikator pendukung
        |--------------------------------------------------------------------------
        |
        | Pada Excel:
        |
        | a. Penambahan Aset RUPTL
        |   - Penambahan JTM
        |   - Penambahan JTR
        |   - Penambahan Trafo
        |
        | Ketiga baris tersebut merupakan data pendukung
        | dari KPI017: "a. Penambahan Aset RUPTL".
        |
        */

        $supportMapping = [
    '-penambahan jtm' => 'KPI017',
    '-penambahan jtr' => 'KPI017',
    '-penambahan trafo' => 'KPI017',
];

        /*
        |--------------------------------------------------------------------------
        | Mapping khusus berdasarkan ID Master KPI
        |--------------------------------------------------------------------------
        |
        | Digunakan untuk nama yang berbeda sedikit antara
        | Excel dan Master KPI.
        |
        */

        $directMapping = [
    'b. kerusakan peralatan distribusi (sesuai kewenangan)'
        => 'KPI035',
];

        /*
        |--------------------------------------------------------------------------
        | Indikator yang sengaja tidak digunakan
        |--------------------------------------------------------------------------
        |
        | Tiga indikator berikut berasal dari bagian Pengendalian Anggaran.
        | Sesuai struktur data yang digunakan saat ini, ketiganya tidak
        | diproses sebagai Realisasi KPI.
        |
        | Karena itu indikator tersebut harus dilewati tanpa dianggap error.
        |
        */

        $ignoredIndicators = [
            'a. pengendalian penggunaan anggaran investasi sesuai rkap',
            'b. usulan penghapusan attb',
            'c. pengendalian nac (non allowable cost)',
        ];

        /*
        |--------------------------------------------------------------------------
        | Mapping nama Excel ke nama Master KPI
        |--------------------------------------------------------------------------
        */

        $mapping = [
            'Penjualan Tenaga Listrik'
                => 'Penambahan Penjualan Tenaga Listrik',

            ' -Penambahan Pelanggan'
                => '-Jumlah Penambahan Pelanggan',

            ' -Penambahan Daya Tersambung'
                => '-Penambahan Daya Tersambung',

            'a. Feedback Rating Negatif pada PLN Mobile - Gangguan'
                => 'a. Feedback Rating Negatif pada PLN Mobile -Gangguan',

            'b. Gangguan TM (sesuai kewenangan)'
                => 'a. Gangguan TM (sesuai kewenangan)',
        ];

        /*
        |--------------------------------------------------------------------------
        | Buat mapping Master KPI berdasarkan nama
        |--------------------------------------------------------------------------
        */

        $kpiMap = [];

        foreach ($masterKpis as $kpi) {

            $nama = $this->normalize(
                $kpi->nama_kpi
            );

            if ($nama === '' || isset($kpiMap[$nama])) {
    $this->errors[] =
        'Nama Master KPI aktif kosong atau tidak unik: '
        . $kpi->nama_kpi
        . '. Periksa master sebelum mengimpor.';

    continue;
}

$kpiMap[$nama] = $kpi;
        }

        /*
        |--------------------------------------------------------------------------
        | Proses setiap baris Excel
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $index => $row) {

            /*
             * Header ada pada baris 3.
             * Data pertama berada pada baris 4.
             */

            $barisExcel = $index + 4;

            /*
             * Ambil indikator.
             */

            $indikatorAsli = trim(
                (string) ($row['indikator'] ?? '')
            );

            /*
             * Lewati baris kosong.
             */

            if ($indikatorAsli === '') {
                continue;
            }

            /*
             * Lewati baris yang hanya berisi angka.
             */

            if (is_numeric($indikatorAsli)) {
                continue;
            }

            /*
             * Ambil realisasi.
             */

            $realisasi = $row['realisasi'] ?? null;

            /*
             * Realisasi kosong tidak diproses.
             */

            if ($realisasi === null || $realisasi === '') {
                continue;
            }

            /*
             * Normalisasi indikator.
             *
             * Dilakukan sebelum pengecekan ignoredIndicators
             * agar perbedaan huruf/spasi tidak menjadi masalah.
             */

            $indikator = $this->normalize(
                $indikatorAsli
            );

            /*
            |--------------------------------------------------------------------------
            | Lewati indikator yang memang tidak digunakan
            |--------------------------------------------------------------------------
            |
            | Ketiga indikator Pengendalian Anggaran tidak dimasukkan
            | ke validRows dan tidak dianggap sebagai error.
            |
            */

            if (in_array($indikator, $ignoredIndicators, true)) {
    $this->warnings[] =
        "Baris {$barisExcel}: \"{$indikatorAsli}\" tidak disimpan "
        . 'karena termasuk indikator yang dikecualikan '
        . 'oleh aturan importer saat ini.';

    continue;
}

            /*
             * Realisasi harus berupa angka.
             */

            if (
    !is_numeric($realisasi)
    || !is_finite((float) $realisasi)
) {

                $this->errors[] =
                    "Baris {$barisExcel}: REALISASI untuk "
                    . "\"{$indikatorAsli}\" harus berupa angka. "
                    . "Nilai: \"{$realisasi}\".";

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Ambil data pendukung
            |--------------------------------------------------------------------------
            */

            $namaPendukung = trim(
                (string) ($row['pendukung'] ?? '')
            );

            /*
             * Beberapa file Excel bisa menghasilkan:
             *
             * satuan_pendukung
             *
             * atau:
             *
             * atuan_pendukung
             *
             * sehingga kita cek keduanya.
             */

            $satuanPendukung = trim(
                (string) (
                    $row['satuan_pendukung']
                    ?? $row['atuan_pendukung']
                    ?? ''
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Cek apakah ini indikator pendukung
            |--------------------------------------------------------------------------
            */

            if (isset($supportMapping[$indikator])) {

                $idKpi = $supportMapping[$indikator];

                $kpi = $masterKpis->firstWhere(
                    'kode_kpi',
                    $idKpi
                );

                if (!$kpi) {

                    $this->errors[] =
                        "Baris {$barisExcel}: KPI induk untuk "
                        . "\"{$indikatorAsli}\" tidak ditemukan.";

                    continue;
                }

                /*
                 * Untuk data pendukung:
                 *
                 * indikator Excel menjadi nama_pendukung
                 *
                 * Contoh:
                 *
                 * -Penambahan JTM
                 *       ↓
                 * nama_pendukung = Penambahan JTM
                 *
                 * id_kpi = KPI017
                 */

                $namaPendukungFinal = ltrim(
                    $indikatorAsli,
                    "- "
                );

                $this->validRows[] = [

                    'id_kpi' => $kpi->id_kpi,

                    'indikator' => $kpi->nama_kpi,

                    'id_pendukung' => null,

                    'nama_pendukung' =>
                        $namaPendukungFinal,

                    'satuan_pendukung' =>
                        $satuanPendukung !== ''
                            ? $satuanPendukung
                            : null,

                    'realisasi' => $realisasi,
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Cek mapping langsung berdasarkan ID
            |--------------------------------------------------------------------------
            */

            $kpi = null;

            if (isset($directMapping[$indikator])) {

                $idKpi = $directMapping[$indikator];

                $kpi = $masterKpis->firstWhere(
                    'kode_kpi',
                    $idKpi
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Jika tidak ada mapping langsung,
            | coba mapping berdasarkan nama
            |--------------------------------------------------------------------------
            */

            if (!$kpi) {

                $namaMaster = $indikator;

                foreach ($mapping as $excelName => $masterName) {

                    if (
                        $this->normalize($excelName)
                        === $indikator
                    ) {

                        $namaMaster =
                            $this->normalize(
                                $masterName
                            );

                        break;
                    }
                }

                $kpi = $kpiMap[$namaMaster] ?? null;
            }

            /*
            |--------------------------------------------------------------------------
            | Jika KPI tidak ditemukan
            |--------------------------------------------------------------------------
            */

            if (!$kpi) {

                $this->errors[] =
                    "Baris {$barisExcel}: indikator "
                    . "\"{$indikatorAsli}\" "
                    . "tidak ditemukan pada Master KPI.";

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Simpan baris valid
            |--------------------------------------------------------------------------
            */

            $this->validRows[] = [

                'id_kpi' => $kpi->id_kpi,

                'indikator' => $kpi->nama_kpi,

                'id_pendukung' => null,

                'nama_pendukung' =>
                    $namaPendukung !== ''
                        ? $namaPendukung
                        : null,

                'satuan_pendukung' =>
                    $satuanPendukung !== ''
                        ? $satuanPendukung
                        : null,

                'realisasi' => $realisasi,
            ];
        }
    }

    /**
     * Normalisasi teks agar perbedaan spasi/huruf
     * tidak menyebabkan gagal pencocokan.
     */
    protected function normalize($value): string
    {
        $value = strtolower(
            trim((string) $value)
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        $value = str_replace(
            '- ',
            '-',
            $value
        );

        return $value;
    }

    /**
     * Ambil daftar error.
     */
    public function getErrors(): array
{
    $errors = $this->errors;
    $seen = [];

    foreach ($this->validRows as $row) {
        $key = json_encode([
            (int) $row['id_kpi'],
            $this->normalize(
                $row['nama_pendukung'] ?? ''
            ),
        ], JSON_THROW_ON_ERROR);

        if (isset($seen[$key])) {
            $errors[] =
                'Realisasi ganda untuk '
                . $row['indikator']
                . ' / '
                . ($row['nama_pendukung'] ?? 'indikator utama')
                . '. Sisakan satu nilai final sebelum mengimpor.';
        }

        $seen[$key] = true;
    }

    return array_values(array_unique($errors));
}

public function getValidRows(): array
{
    return $this->validRows;
}

public function getWarnings(): array
{
    return array_values(array_unique($this->warnings));
}
}