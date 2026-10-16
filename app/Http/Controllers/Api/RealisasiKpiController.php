<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RealisasiKpi;
use Illuminate\Http\Request;

class RealisasiKpiController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'id_ulp' => 'nullable|integer|min:1',
            'id_periode' => 'nullable|integer|min:1',
        ]);

        $query = RealisasiKpi::with([
            'ulp:id_ulp,nama_ulp',
            'kpi:id_kpi,kode_kpi,nama_kpi,bobot,polaritas',
            'periode',
        ]);

        if ($request->filled('id_ulp')) {
            $query->where('id_ulp', $request->id_ulp);
        }

        if ($request->filled('id_periode')) {
            $query->where('id_periode', $request->id_periode);
        }

        $data = $query
            ->orderBy('id_kpi')
            ->orderBy('nama_pendukung')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data Realisasi KPI berhasil diambil',
            'total' => $data->count(),
            'filters' => [
                'id_ulp' => $request->id_ulp,
                'id_periode' => $request->id_periode,
            ],
            'data' => $data,
        ]);
    }
}