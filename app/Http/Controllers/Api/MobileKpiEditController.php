<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TargetKpiEditController;
use App\Models\RealisasiKpi;
use App\Models\Ulp;
use App\Services\KpiCalculationService;
use App\Services\TargetRealisasiSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MobileKpiEditController extends Controller
{
    private function requireAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'admin',
            403,
            'Edit target hanya tersedia untuk Admin.'
        );
    }

    private function requireUnit(Request $request): int
    {
        $user = $request->user();

        abort_unless(
            $user && $user->role === 'ulp',
            403,
            'Edit realisasi hanya tersedia untuk akun ULP.'
        );

        abort_unless(
            $user->id_ulp,
            403,
            'Akun belum memiliki ULP.'
        );

        $unitId = (int) $user->id_ulp;

        abort_unless(
            Ulp::query()
                ->whereKey($unitId)
                ->where('status', 'aktif')
                ->exists(),
            403,
            'ULP akun tidak tersedia atau tidak aktif.'
        );

        return $unitId;
    }

    private function version(RealisasiKpi $record): string
    {
        $attributes = $record->getRawOriginal();

        ksort($attributes);

        return hash(
            'sha256',
            json_encode($attributes, JSON_THROW_ON_ERROR)
        );
    }

    public function showTarget(
        Request $request,
        int $target,
        TargetKpiEditController $controller
    ) {
        $this->requireAdmin($request);

        return $controller->show($target);
    }

    public function updateTarget(
        Request $request,
        int $target,
        TargetKpiEditController $controller,
        TargetRealisasiSyncService $sync
    ) {
        $this->requireAdmin($request);

        // Memakai validasi, transaksi, pemeriksaan versi,
        // dan sinkronisasi target yang sama dengan website.
        return $controller->update($request, $target, $sync);
    }

    public function showRealisasi(
        Request $request,
        int $realisasi
    ) {
        $unitId = $this->requireUnit($request);

        $record = RealisasiKpi::query()
            ->where('id_ulp', $unitId)
            ->with(['kpi', 'periode'])
            ->findOrFail($realisasi);

        return response()->json([
            'success' => true,
            'data' => [
                'id_realisasi' => $record->id_realisasi,
                'id_ulp' => $record->id_ulp,
                'realisasi' => $record->realisasi,
                'target_periode' => $record->target_periode,
                'nama_pendukung' => $record->nama_pendukung,
                'satuan_pendukung' => $record->satuan_pendukung,
                'kpi' => $record->kpi,
                'periode' => $record->periode,
                'version' => $this->version($record),
            ],
        ]);
    }

    public function updateRealisasi(
        Request $request,
        int $realisasi,
        KpiCalculationService $kalkulasi
    ) {
        $unitId = $this->requireUnit($request);

        // Periksa kepemilikan sebelum memvalidasi isi formulir.
        RealisasiKpi::query()
            ->where('id_ulp', $unitId)
            ->findOrFail($realisasi);

        $validated = $request->validate([
            'version' => ['required', 'string', 'size:64'],
            'realisasi' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if (
                        is_numeric($value)
                        && !is_finite((float) $value)
                    ) {
                        $fail('Nilai realisasi tidak valid.');
                    }
                },
            ],
        ], [
            'version.required' =>
                'Tutup lalu buka kembali formulir edit.',
            'version.size' =>
                'Versi formulir tidak valid.',
            'realisasi.required' =>
                'Nilai realisasi wajib diisi.',
            'realisasi.numeric' =>
                'Nilai realisasi harus berupa angka.',
        ]);

        try {
            DB::transaction(function () use (
                $unitId,
                $realisasi,
                $validated,
                $kalkulasi
            ) {
                // Urutan penguncian mengikuti alur edit/impor website.
                $unit = Ulp::query()
                    ->whereKey($unitId)
                    ->where('status', 'aktif')
                    ->lockForUpdate()
                    ->first();

                abort_unless(
                    $unit,
                    403,
                    'ULP akun sudah tidak aktif atau tidak tersedia.'
                );

                $record = RealisasiKpi::query()
                    ->where('id_ulp', $unitId)
                    ->whereKey($realisasi)
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless(
                    hash_equals(
                        $this->version($record),
                        $validated['version']
                    ),
                    409,
                    'Data sudah berubah. Tutup formulir lalu buka '
                    . 'kembali untuk mengambil data terbaru.'
                );

                $record->realisasi = $validated['realisasi'];
                $record->save();

                // Hitung memakai presisi nilai yang tersimpan di DB.
                $record->refresh();

                $hasil = $kalkulasi->hitung($record);

                $record->pencapaian = $hasil['pencapaian'] ?? null;
                $record->nilai_kpi = $hasil['nilai_kpi'] ?? null;

                $record->save();
            }, 3);
        } catch (
            \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            | \Illuminate\Database\Eloquent\ModelNotFoundException $e
        ) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Edit realisasi melalui API gagal.', [
                'id_realisasi' => $realisasi,
                'id_ulp' => $unitId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' =>
                    'Realisasi gagal disimpan. Perubahan dibatalkan. '
                    . 'Periksa log Laravel.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Realisasi berhasil diperbarui. Pencapaian dan '
                . 'nilai KPI dihitung ulang jika target dan '
                . 'definisi KPI tersedia.',
        ]);
    }
}