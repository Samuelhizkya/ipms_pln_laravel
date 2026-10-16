<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TargetKpi;
use Illuminate\Http\Request;

class TargetKpiController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'id_ulp' => 'nullable|integer|min:1',
            'tahun' => 'nullable|integer|min:2020|max:2100',
        ]);

        $query = TargetKpi::with([
            'ulp',
            'kpi',
            'targetBulanan'
        ]);

        if ($request->filled('id_ulp')) {
            $query->where('id_ulp', $request->id_ulp);
        }

        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        $data = $query->orderBy('id_kpi')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data Target KPI berhasil diambil',
            'total' => $data->count(),
            'filters' => [
                'id_ulp' => $request->id_ulp,
                'tahun' => $request->tahun,
            ],
            'data' => $data
        ]);
    }
}
