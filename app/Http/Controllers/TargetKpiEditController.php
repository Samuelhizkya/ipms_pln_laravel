<?php

namespace App\Http\Controllers;

use App\Models\TargetKpi;
use App\Models\Ulp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TargetRealisasiSyncService;
use Illuminate\Support\Facades\Log;

class TargetKpiEditController extends Controller
{
    private function snapshot(TargetKpi $target): array
    {
        $monthly = $target->targetBulanan()
            ->orderBy('bulan')
            ->get();

        abort_if(
            $monthly->pluck('bulan')->duplicates()->isNotEmpty(),
            409,
            'Ada target bulanan ganda. Hubungi pengelola data.'
        );

        $values = [];

        for ($month = 1; $month <= 12; $month++) {
            $row = $monthly->firstWhere('bulan', $month);

            $values[] = [
                'bulan' => $month,
                'nilai_target' => $row?->nilai_target,
            ];
        }

        $data = [
            'id_target' => $target->id_target,
            'id_ulp' => $target->id_ulp,
            'id_kpi' => $target->id_kpi,
            'tahun' => $target->tahun,
            'target_tahunan' => $target->target_tahunan,
            'bulanan' => $values,
        ];

        $data['version'] = hash(
            'sha256',
            json_encode($data, JSON_THROW_ON_ERROR)
        );

        return $data;
    }

    private function ensureActiveUnit(TargetKpi $target): void
    {
        abort_unless(
            Ulp::query()
                ->where('id_ulp', $target->id_ulp)
                ->where('status', 'aktif')
                ->exists(),
            422,
            'ULP target tidak tersedia atau tidak aktif.'
        );
    }

    public function show(int $target)
    {
        $record = TargetKpi::findOrFail($target);

        $this->ensureActiveUnit($record);

        return response()->json([
            'success' => true,
            'data' => $this->snapshot($record),
        ]);
    }

    public function update(
    Request $request,
    int $target,
    TargetRealisasiSyncService $sync
) {
    $finite = function ($attribute, $value, $fail) {
        if (
            is_numeric($value)
            && !is_finite((float) $value)
        ) {
            $fail('Nilai target terlalu besar atau tidak valid.');
        }
    };

    $rules = [
        'version' => [
            'required',
            'string',
            'size:64',
        ],
        'target_tahunan' => [
            'required',
            'numeric',
            $finite,
        ],
        'bulanan' => [
            'required',
            'array',
            'size:12',
        ],
    ];

    for ($index = 0; $index < 12; $index++) {
        $rules["bulanan.$index"] = [
            'present',
            'nullable',
            'numeric',
            $finite,
        ];
    }

    $validated = $request->validate($rules);

    $unitId = (int) TargetKpi::findOrFail($target)->id_ulp;

    try {
        $result = DB::transaction(
            function () use (
                $target,
                $unitId,
                $validated,
                $sync
            ) {
                $unit = Ulp::query()
                    ->whereKey($unitId)
                    ->where('status', 'aktif')
                    ->lockForUpdate()
                    ->first();

                abort_unless(
                    $unit,
                    422,
                    'ULP target tidak tersedia atau tidak aktif.'
                );

                $record = TargetKpi::query()
                    ->whereKey($target)
                    ->where('id_ulp', $unitId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $duplicates = TargetKpi::query()
                    ->where('id_ulp', $record->id_ulp)
                    ->where('id_kpi', $record->id_kpi)
                    ->where('tahun', $record->tahun)
                    ->lockForUpdate()
                    ->get(['id_target']);

                abort_if(
                    $duplicates->count() !== 1,
                    409,
                    'Ada target tahunan ganda. Hubungi pengelola data.'
                );

                $current = $this->snapshot($record);

                abort_unless(
                    hash_equals(
                        $current['version'],
                        $validated['version']
                    ),
                    409,
                    'Target sudah berubah. Tutup lalu buka kembali Edit Target.'
                );

                $record->target_tahunan =
                    $validated['target_tahunan'];

                $record->save();

                foreach ($validated['bulanan'] as $index => $value) {
                    $month = (int) $index + 1;

                    if ($value === null) {
                        $record->targetBulanan()
                            ->where('bulan', $month)
                            ->delete();
                    } else {
                        $record->targetBulanan()->updateOrCreate(
                            ['bulan' => $month],
                            ['nilai_target' => $value]
                        );
                    }
                }

                $record->refresh();

                return [
                    'id_ulp' => (int) $record->id_ulp,
                    'tahun' => (int) $record->tahun,
                    'jumlah_realisasi' => $sync->sync($record),
                ];
            },
            3
        );
    } catch (
        \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
        | \Illuminate\Database\Eloquent\ModelNotFoundException $e
    ) {
        throw $e;
    } catch (\Throwable $e) {
        Log::error('Penyimpanan edit target gagal.', [
            'id_target' => $target,
            'exception' => $e,
        ]);

        return response()->json([
            'success' => false,
            'message' =>
                'Target gagal disimpan. Perubahan dibatalkan. '
                . 'Periksa log Laravel.',
        ], 500);
    }

    $message =
        'Target berhasil diperbarui. '
        . $result['jumlah_realisasi']
        . ' baris realisasi terkait telah disesuaikan.';

if ($request->hasSession()) {
    $request->session()->flash('success', $message);
}

    return response()->json([
        'success' => true,
        'message' => $message,
        'redirect' => route('target.kpi', [
            'id_ulp' => $result['id_ulp'],
            'tahun' => $result['tahun'],
        ]),
    ]);
}
}