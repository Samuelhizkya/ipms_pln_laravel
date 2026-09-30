<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterKpi;

class MasterKpiController extends Controller
{
    public function index()
    {
        $kpis = MasterKpi::where('status', 'aktif')
                         ->orderBy('kode_kpi')
                         ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data Master KPI berhasil diambil',
            'data' => $kpis
        ]);
    }
}