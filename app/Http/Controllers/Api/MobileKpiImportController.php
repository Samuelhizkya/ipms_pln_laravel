<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\RealisasiKpiImport;
use App\Imports\TargetKpiImport;
use App\Models\PeriodeKinerja;
use App\Models\RealisasiKpi;
use App\Models\TargetKpi;
use App\Models\Ulp;
use App\Services\KpiCalculationService;
use App\Services\KpiImportFileValidator;
use App\Services\TargetRealisasiSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class MobileKpiImportController extends Controller
{
    private function requireRole(Request $request, string $role): void
    {
        abort_unless(
            $request->user()?->role === $role,
            403,
            'Akun tidak memiliki akses untuk jenis impor ini.'
        );
    }

    private function accountUnit(Request $request): int
    {
        $this->requireRole($request, 'ulp');

        $id = (int) $request->user()->id_ulp;

        abort_unless(
            $id > 0
                && Ulp::whereKey($id)
                    ->where('status', 'aktif')
                    ->exists(),
            403,
            'ULP akun tidak tersedia atau tidak aktif.'
        );

        return $id;
    }

    private function lockUnit(int $id): void
    {
        $unit = Ulp::query()
            ->whereKey($id)
            ->where('status', 'aktif')
            ->lockForUpdate()
            ->first();

        abort_unless(
            $unit,
            403,
            'ULP tidak tersedia atau sudah tidak aktif.'
        );
    }

    private function readRows(
        TargetKpiImport|RealisasiKpiImport $import,
        UploadedFile $file
    ): array {
        try {
            Excel::import($import, $file);
        } catch (Throwable $error) {
            report($error);

            throw ValidationException::withMessages([
                'file' =>
                    'File gagal dibaca. Gunakan template yang sesuai '
                    . 'dan pastikan file tidak rusak.',
            ]);
        }

        $errors = $import->getErrors();

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => $errors,
            ]);
        }

        $rows = $import->getValidRows();

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada data valid yang dapat diimpor.',
            ]);
        }

        return $rows;
    }

    public function periods(Request $request)
    {
        $this->accountUnit($request);

        $data = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
        ]);

        return response()->json([
            'success' => true,
            'data' => PeriodeKinerja::query()
                ->where('tahun', $data['tahun'])
                ->whereBetween('bulan', [1, 12])
                ->orderBy('bulan')
                ->orderBy('id_periode')
                ->get(['id_periode', 'tahun', 'bulan', 'status']),
        ]);
    }

    public function target(
        Request $request,
        KpiImportFileValidator $validator,
        TargetRealisasiSyncService $sync
    ) {
        $this->requireRole($request, 'admin');

        $data = $request->validate([
            'id_ulp' => [
                'required',
                'integer',
                Rule::exists('ulp', 'id_ulp')->where('status', 'aktif'),
            ],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'confirmed' => ['required', 'accepted'],
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ], [
            'file.required' => 'Pilih file target terlebih dahulu.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
            'file.mimes' => 'Gunakan file XLSX, XLS, atau CSV.',
            'confirmed.accepted' => 'Konfirmasi tujuan impor terlebih dahulu.',
        ]);

        $unitId = (int) $data['id_ulp'];
        $year = (int) $data['tahun'];
        $file = $request->file('file');

        $validator->validate($file, 'target', $year);

        $result = DB::transaction(function () use (
            $unitId,
            $year,
            $file,
            $sync
        ) {
            $this->lockUnit($unitId);

            $import = new TargetKpiImport(
    $unitId,
    $year,
    allowMissingBudget: true
);
            $rows = $this->readRows($import, $file);

            $seen = [];
            $synced = 0;

            foreach ($rows as $row) {
                $kpiId = (int) $row['id_kpi'];

                abort_if(
                    isset($seen[$kpiId]),
                    422,
                    'File menghasilkan target ganda untuk KPI yang sama.'
                );

                $seen[$kpiId] = true;

                $matches = TargetKpi::query()
                    ->where('id_ulp', $unitId)
                    ->where('id_kpi', $kpiId)
                    ->where('tahun', $year)
                    ->lockForUpdate()
                    ->get();

                abort_if(
                    $matches->count() > 1,
                    409,
                    'Ada target tahunan ganda di database. '
                    . 'Impor dibatalkan.'
                );

                $target = $matches->first();

                if (!$target) {
                    $target = new TargetKpi([
                        'id_ulp' => $unitId,
                        'id_kpi' => $kpiId,
                        'tahun' => $year,
                    ]);
                }

                $target->target_tahunan = $row['target_tahunan'];
                $target->save();

                // Hanya bulan yang dipetakan importer yang diperbarui.
                foreach ($row['bulanan'] as $month => $value) {
                    $month = (int) $month;

                    abort_if(
                        $month < 1 || $month > 12,
                        422,
                        'Nomor bulan target tidak valid.'
                    );

                    $target->targetBulanan()
                        ->where('bulan', $month)
                        ->delete();

                    // Nilai kosong tetap kosong; nol tetap disimpan.
                    if ($value !== null && $value !== '') {
                        $target->targetBulanan()->create([
                            'bulan' => $month,
                            'nilai_target' => $value,
                        ]);
                    }
                }

                $target->refresh();
                $synced += $sync->sync($target);
            }

            return [
    'jumlah' => count($rows),
    'jumlah_realisasi' => $synced,
    'warnings' => $import->getWarnings(),
];
        }, 3);

        return response()->json([
    'success' => true,
    'message' =>
        $result['jumlah'] . ' target KPI berhasil disimpan. '
        . (
            $result['warnings'] !== []
                ? count($result['warnings'])
                    . ' baris belum diimpor. Periksa rincian peringatan. '
                : ''
        )
        . $result['jumlah_realisasi']
        . ' baris realisasi terkait disinkronkan.',
    'data' => [
        'tahun' => $year,
        'jumlah' => $result['jumlah'],
        'jumlah_realisasi' => $result['jumlah_realisasi'],
        'jumlah_dilewati' => count($result['warnings']),
        'warnings' => $result['warnings'],
    ],
]);
    }
    public function realisasi(
        Request $request,
        KpiImportFileValidator $validator,
        KpiCalculationService $calculation
    ) {
        // ULP selalu diambil dari akun, bukan dari isi unggahan.
        $unitId = $this->accountUnit($request);

        $data = $request->validate([
            'id_periode' => [
                'required',
                'integer',
                'exists:periode_kinerja,id_periode',
            ],
            'confirmed' => ['required', 'accepted'],
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ], [
            'file.required' => 'Pilih file realisasi terlebih dahulu.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
            'file.mimes' => 'Gunakan file XLSX atau XLS.',
            'id_periode.exists' => 'Periode tidak tersedia.',
        ]);

        $periodId = (int) $data['id_periode'];
        $file = $request->file('file');

        $validator->validate($file, 'realisasi');

        $result = DB::transaction(function () use (
            $unitId,
            $periodId,
            $file,
            $calculation
        ) {
            $this->lockUnit($unitId);

            $period = PeriodeKinerja::query()
                ->whereKey($periodId)
                ->sharedLock()
                ->firstOrFail();

            abort_if(
                (int) $period->bulan < 1 || (int) $period->bulan > 12,
                422,
                'Periode tidak valid.'
            );

            $existing = RealisasiKpi::query()
                ->where('id_ulp', $unitId)
                ->where('id_periode', $periodId)
                ->lockForUpdate()
                ->first();

            abort_if(
                $existing !== null,
                409,
                'ULP dan periode ini sudah memiliki realisasi. '
                . 'Gunakan Edit realisasi untuk memperbaiki data.'
            );

            $import = new RealisasiKpiImport();
            $rows = $this->readRows($import, $file);

            foreach ($rows as $row) {
                $targets = TargetKpi::query()
                    ->where('id_ulp', $unitId)
                    ->where('id_kpi', $row['id_kpi'])
                    ->where('tahun', $period->tahun)
                    ->lockForUpdate()
                    ->get();

                abort_if(
                    $targets->count() > 1,
                    409,
                    'Target tahunan ganda ditemukan. Impor dibatalkan.'
                );

                $target = $targets->first();
                $monthlyValue = null;

                if ($target) {
                    $monthly = $target->targetBulanan()
                        ->where('bulan', (int) $period->bulan)
                        ->get();

                    abort_if(
                        $monthly->count() > 1,
                        409,
                        'Target bulanan ganda ditemukan. Impor dibatalkan.'
                    );

                    $monthlyValue = $monthly->first()?->nilai_target;
                }

                $record = new RealisasiKpi([
                    'id_ulp' => $unitId,
                    'id_kpi' => $row['id_kpi'],
                    'id_pendukung' => $row['id_pendukung'] ?? null,
                    'nama_pendukung' => $row['nama_pendukung'] ?? null,
                    'satuan_pendukung' => $row['satuan_pendukung'] ?? null,
                    'id_periode' => $periodId,
                    'target_tahunan' => $target?->target_tahunan,
                    'target_periode' => $monthlyValue,
                    'realisasi' => $row['realisasi'],
                    'pencapaian' => null,
                    'nilai_kpi' => null,
                    'sumber_data' => 'Excel',
                    'status_data' => 'valid',
                ]);

                $record->save();
                $record->refresh();

                $hasil = $calculation->hitung($record);

                $record->pencapaian = $hasil['pencapaian'] ?? null;
                $record->nilai_kpi = $hasil['nilai_kpi'] ?? null;
                $record->save();
            }

            return [
                'jumlah' => count($rows),
                'tahun' => (int) $period->tahun,
                'bulan' => (int) $period->bulan,
                'warnings' => $import->getWarnings(),
            ];
        }, 3);

        return response()->json([
            'success' => true,
            'message' =>
                $result['jumlah'] . ' baris realisasi berhasil diimpor.',
            'data' => $result,
        ]);
    }
}