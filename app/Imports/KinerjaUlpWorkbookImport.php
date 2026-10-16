<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KinerjaUlpWorkbookImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'KINERJA ULP' => new KinerjaUlpSheetImport(),
        ];
    }
}
