<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Throwable;
use ZipArchive;

class KpiImportFileValidator
{
    /*
     * Batas operasional impor melalui browser.
     * File di luar batas ditolak, bukan dipotong.
     */
    private const MAX_ROWS = 2000;
    private const MAX_COLUMNS = 64;
    private const MAX_SHEETS = 5;
    private const MAX_EXPANDED_BYTES = 32 * 1024 * 1024;

    public function validate(
        UploadedFile $file,
        string $kind,
        ?int $year = null
    ): void {
        if (!in_array($kind, ['target', 'realisasi'], true)) {
            throw new \InvalidArgumentException(
                'Jenis impor tidak dikenal.'
            );
        }

        if ($kind === 'target' && $year === null) {
            throw new \InvalidArgumentException(
                'Tahun diperlukan untuk pemeriksaan target.'
            );
        }

        $path = $file->getRealPath();

        if (!$path || !$file->isValid()) {
            $this->reject(
                'Unggahan tidak lengkap. Pilih kembali file.'
            );
        }

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $allowed = $kind === 'target'
            ? ['xlsx', 'xls', 'csv']
            : ['xlsx', 'xls'];

        if (!in_array($extension, $allowed, true)) {
            $this->reject(
                'Format file tidak sesuai. Gunakan: '
                . implode(', ', $allowed)
                . '.'
            );
        }

        $spreadsheet = null;

        try {
            /*
             * XLSX merupakan arsip ZIP.
             * Periksa ukuran setelah diekstrak sebelum dibaca.
             */
            if ($extension === 'xlsx') {
                $this->checkXlsxArchive($path);
            }

            $readerType = match ($extension) {
                'xlsx' => 'Xlsx',
                'xls' => 'Xls',
                'csv' => 'Csv',
            };

            $reader = IOFactory::createReader($readerType);

            if (!$reader->canRead($path)) {
                $this->reject(
                    'Isi file tidak sesuai dengan formatnya, '
                    . 'atau file rusak. Gunakan file asli dari template.'
                );
            }

            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);

            /*
             * Membaca informasi sheet tanpa membentuk
             * seluruh objek sel workbook.
             */
            $sheets = $reader->listWorksheetInfo($path);

            if (!$sheets) {
                $this->reject(
                    'File tidak mempunyai worksheet yang dapat dibaca.'
                );
            }

            if (count($sheets) > self::MAX_SHEETS) {
                $this->reject(
                    'File mempunyai terlalu banyak sheet. '
                    . 'Maksimal '
                    . self::MAX_SHEETS
                    . ' sheet untuk impor melalui halaman ini.'
                );
            }

            foreach ($sheets as $sheet) {
                $rows = (int) ($sheet['totalRows'] ?? 0);
                $columns = (int) ($sheet['totalColumns'] ?? 0);
                $name = (string) ($sheet['worksheetName'] ?? '');

                if (
                    $rows > self::MAX_ROWS
                    || $columns > self::MAX_COLUMNS
                ) {
                    $this->reject(
                        'Sheet "' . $name . '" terlalu luas. '
                        . 'Batas impor adalah '
                        . self::MAX_ROWS
                        . ' baris dan '
                        . self::MAX_COLUMNS
                        . ' kolom per sheet. '
                        . 'Gunakan template KPI dan hapus baris/kolom '
                        . 'tambahan yang tidak diperlukan.'
                    );
                }
            }

            $headingRow = $kind === 'target' ? 1 : 3;

            /*
             * Pada tahap ini hanya baris header yang dimuat.
             * Baris data belum dibaca untuk proses impor.
             */
            $reader->setReadFilter(
                new class($headingRow) implements IReadFilter {
                    public function __construct(
                        private int $headingRow
                    ) {
                    }

                    public function readCell(
                        $columnAddress,
                        $row,
                        $worksheetName = ''
                    ): bool {
                        return (int) $row === $this->headingRow;
                    }
                }
            );

            $spreadsheet = $reader->load($path);

            $requiredHeaders = $kind === 'target'
                ? [
                    'indikator',
                    'target_' . $year,
                    'sd_jan',
                    'sd_feb',
                    'sd_mar',
                    'sd_apr',
                    'sd_may',
                    'sd_jun',
                    'sd_jul',
                    'sd_aug',
                    'sd_sep',
                    'sd_oct',
                    'sd_nov',
                ]
                : [
                    'indikator',
                    'pendukung',
                    'realisasi',
                ];

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $lastColumn = $sheet->getHighestDataColumn(
                    $headingRow
                );

                $range = 'A' . $headingRow
                    . ':' . $lastColumn . $headingRow;

                $headerRows = $sheet->rangeToArray(
                    $range,
                    null,
                    false,
                    false
                );

                $headers = HeadingRowFormatter::format(
                    $headerRows[0] ?? []
                );

                $missing = array_values(
                    array_diff($requiredHeaders, $headers)
                );

                if (
                    $kind === 'realisasi'
                    && !in_array('satuan_pendukung', $headers, true)
                    && !in_array('atuan_pendukung', $headers, true)
                ) {
                    $missing[] = 'satuan_pendukung';
                }

                if ($missing !== []) {
                    $this->reject(
                        'File bukan template '
                        . ($kind === 'target'
                            ? 'Target KPI'
                            : 'Realisasi KPI')
                        . ' yang sesuai. Sheet "'
                        . $sheet->getTitle()
                        . '", header baris '
                        . $headingRow
                        . '. Kolom yang belum ditemukan: '
                        . implode(', ', $missing)
                        . '.'
                    );
                }
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            $this->reject(
                'File tidak dapat dibaca. Pastikan file tidak rusak, '
                . 'tidak dilindungi password, dan menggunakan '
                . 'template KPI yang sesuai.'
            );
        } finally {
            if ($spreadsheet !== null) {
                $spreadsheet->disconnectWorksheets();
            }
        }
    }

    private function checkXlsxArchive(string $path): void
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            $this->reject(
                'File XLSX rusak atau tidak dapat dibuka.'
            );
        }

        try {
            if (
                $zip->locateName('[Content_Types].xml') === false
                || $zip->locateName('xl/workbook.xml') === false
            ) {
                $this->reject(
                    'File tersebut bukan workbook XLSX yang valid.'
                );
            }

            $totalSize = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);

                if ($entry === false) {
                    $this->reject(
                        'Struktur file XLSX tidak dapat dibaca.'
                    );
                }

                $totalSize += (int) ($entry['size'] ?? 0);

                if ($totalSize > self::MAX_EXPANDED_BYTES) {
                    $this->reject(
                        'Isi workbook terlalu besar setelah diekstrak. '
                        . 'Gunakan salinan template yang hanya berisi '
                        . 'data KPI yang diperlukan.'
                    );
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'file' => $message,
        ]);
    }
}