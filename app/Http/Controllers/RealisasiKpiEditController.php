<?php

namespace App\Http\Controllers;

use App\Models\RealisasiKpi;
use App\Models\Ulp;
use App\Services\KpiCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RealisasiKpiEditController extends Controller
{
    private function unitId(Request $request): int
    {
        abort_unless(
            $request->session()->get('user.role') === 'ulp',
            403,
            'Edit realisasi hanya tersedia untuk akun ULP.'
        );

        $unit = $request->attributes->get('account_ulp');

        abort_unless(
            $unit instanceof Ulp
                && (int) $unit->id_ulp ===
                    (int) $request->session()->get('user.id_ulp'),
            403,
            'Identitas ULP tidak tersedia.'
        );

        return (int) $unit->id_ulp;
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

    public function edit(Request $request, int $realisasi)
    {
        $unitId = $this->unitId($request);

        $record = RealisasiKpi::query()
            ->where('id_ulp', $unitId)
            ->with(['ulp', 'kpi', 'periode'])
            ->findOrFail($realisasi);

        $version = $this->version($record);

        return view(
            'kpi.realisasi-edit',
            compact('record', 'version')
        );
    }

    public function update(
        Request $request,
        int $realisasi,
        KpiCalculationService $kalkulasi
    ) {
        $unitId = $this->unitId($request);

        // Periksa kepemilikan sebelum memproses formulir.
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
                'Buka kembali formulir edit realisasi.',
            'version.size' =>
                'Formulir tidak valid. Buka kembali halaman edit.',
            'realisasi.required' =>
                'Nilai realisasi wajib diisi.',
            'realisasi.numeric' =>
                'Nilai realisasi harus berupa angka.',
        ]);

        try {
            $result = DB::transaction(function () use (
                $unitId,
                $realisasi,
                $validated,
                $kalkulasi
            ) {
                $unit = Ulp::query()
                    ->where('id_ulp', $unitId)
                    ->where('status', 'aktif')
                    ->lockForUpdate()
                    ->first();

                if (!$unit) {
                    throw ValidationException::withMessages([
                        'realisasi' =>
                            'ULP akun tidak tersedia atau tidak aktif.',
                    ]);
                }

                $record = RealisasiKpi::query()
                    ->where('id_ulp', $unitId)
                    ->whereKey($realisasi)
                    ->lockForUpdate()
                    ->first();

                if (!$record) {
                    throw ValidationException::withMessages([
                        'realisasi' =>
                            'Data realisasi sudah tidak tersedia.',
                    ]);
                }

                if (!hash_equals(
                    $this->version($record),
                    $validated['version']
                )) {
                    throw ValidationException::withMessages([
                        'version' =>
                            'Data sudah berubah sejak formulir dibuka. '
                            . 'Klik Muat ulang data sebelum mengedit kembali.',
                    ]);
                }

                $record->realisasi = $validated['realisasi'];
                $record->save();

                // Gunakan angka sesuai presisi yang disimpan database.
                $record->refresh();

                /*
                 * Gunakan target yang tersimpan pada baris realisasi.
                 * Impor/edit target menyinkronkan nilai target tersebut.
                 */
                $hasil = $kalkulasi->hitung($record);

                $record->pencapaian = $hasil['pencapaian'] ?? null;
                $record->nilai_kpi = $hasil['nilai_kpi'] ?? null;

                $record->save();

                $record->load('periode');

                return [
                    'id_ulp' => $unitId,
                    'id_periode' => (int) $record->id_periode,
                    'tahun' => $record->periode?->tahun,
                    'bulan' => $record->periode?->bulan,
                ];
            }, 3);
        } catch (ValidationException $e) {
            return back()
                ->withInput($request->only('realisasi', 'version'))
                ->withErrors($e->errors());
        } catch (Throwable $e) {
            Log::error('Edit Realisasi KPI gagal.', [
                'id_realisasi' => $realisasi,
                'id_ulp' => $unitId,
                'exception' => $e,
            ]);

            return back()
                ->withInput($request->only('realisasi', 'version'))
                ->with(
                    'error',
                    'Perubahan gagal disimpan. Data sebelumnya tetap '
                    . 'dipertahankan. Periksa log Laravel.'
                );
        }

        return redirect()
            ->route('realisasi.kpi', $result)
            ->with(
                'success',
                'Realisasi berhasil diperbarui. Pencapaian dan nilai '
                . 'KPI telah dihitung ulang sesuai target yang tersedia.'
            );
    }
}