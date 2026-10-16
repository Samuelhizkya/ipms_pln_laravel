<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class KinerjaUlpSheetImport implements ToCollection, WithStartRow
{
    protected array $rows = [];

    /**
     * Data KPI dimulai dari baris ke-10.
     */
    public function startRow(): int
    {
        return 10;
    }

    /**
     * Membaca data dari sheet KINERJA ULP.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $indikator = trim((string) ($row[2] ?? ''));

            // Lewati baris tanpa nama indikator.
            if ($indikator === '') {
                continue;
            }

            $this->rows[] = [
                'baris_excel' => $index + 10,
                'nomor' => $row[1] ?? null,
                'indikator' => $indikator,
                'polaritas' => $this->cleanText($row[3] ?? null),
                'formula' => $this->cleanText($row[4] ?? null),
                'satuan' => $this->cleanText($row[5] ?? null),
                'bobot' => $this->cleanNumber($row[6] ?? null),
                'target_tahunan' => $this->cleanNumber($row[7] ?? null),
                'nilai_pembanding' => $this->cleanNumber($row[8] ?? null),
                'realisasi' => $this->cleanNumber($row[9] ?? null),
                'pencapaian' => $this->cleanNumber($row[10] ?? null),
                'nilai_kpi' => $this->cleanNumber($row[11] ?? null),
            ];
        }
    }

    /**
     * Membersihkan nilai teks.
     */
    protected function cleanText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Mengubah nilai numerik menjadi angka.
     */
    protected function cleanNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            // Jangan menganggap error formula Excel sebagai angka.
            if ($value === '' || str_starts_with($value, '#')) {
                return null;
            }

            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Mengambil hasil pembacaan Excel.
     */
    public function getRows(): array
    {
        return $this->rows;
    }
}
