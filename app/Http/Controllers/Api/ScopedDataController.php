<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KpiUlp;
use App\Models\TargetKpiBulanan;
use App\Models\Ulp;
use Illuminate\Http\Request;

class ScopedDataController extends Controller
{
    public function units(Request $request)
    {
        $user = $request->user();

        $query = Ulp::query()
            ->where('status', 'aktif')
            ->orderBy('nama_ulp');

        if ($user->role === 'ulp') {
            abort_unless(
                $user->id_ulp,
                403,
                'Akun belum memiliki ULP. Hubungi Admin.'
            );

            $query->where('id_ulp', $user->id_ulp);
        }

        $data = $query->get();

        abort_if(
            $user->role === 'ulp' && $data->isEmpty(),
            403,
            'ULP akun tidak tersedia atau tidak aktif.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Data ULP berhasil diambil.',
            'data' => $data,
        ]);
    }

    public function kpiUlp(Request $request)
    {
        $data = KpiUlp::query()
            ->with(['kpi', 'ulp'])
            ->where('id_ulp', (int) $request->input('id_ulp'))
            ->where('status', 'aktif')
            ->orderBy('id_kpi')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data KPI ULP berhasil diambil.',
            'data' => $data,
        ]);
    }

    public function monthlyTargets(Request $request)
    {
        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $unitId = (int) $request->input('id_ulp');

        $query = TargetKpiBulanan::query()
            ->with(['targetKpi.ulp', 'targetKpi.kpi'])
            ->whereHas('targetKpi', function ($target) use (
                $unitId,
                $validated
            ) {
                $target->where('id_ulp', $unitId);

                if (isset($validated['tahun'])) {
                    $target->where('tahun', $validated['tahun']);
                }
            });

        if (isset($validated['bulan'])) {
            $query->where('bulan', $validated['bulan']);
        }

        $data = $query
            ->orderBy('id_target')
            ->orderBy('bulan')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data target bulanan berhasil diambil.',
            'total' => $data->count(),
            'data' => $data,
        ]);
    }
}