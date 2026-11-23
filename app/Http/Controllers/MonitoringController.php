<?php

namespace App\Http\Controllers;

use App\Models\RealisasiKpi;
use App\Models\Ulp;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index()
    {
        return view('monitoring.index');
    }

    public function units()
    {
        $data = Ulp::query()->where('status', 'aktif')->orderBy('nama_ulp')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function realisasi(Request $request)
    {
        $filters = $request->validate([
            'id_ulp' => 'nullable|integer|min:1',
            'id_periode' => 'nullable|integer|min:1',
        ]);

        $query = RealisasiKpi::with(['ulp', 'kpi', 'periode']);
        foreach (['id_ulp', 'id_periode'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        $data = $query->orderBy('id_kpi')->orderBy('nama_pendukung')->get();
        return response()->json([
            'success' => true,
            'total' => $data->count(),
            'data' => $data,
        ]);
    }
}
