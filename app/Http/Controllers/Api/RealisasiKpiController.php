<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RealisasiKpi;

class RealisasiKpiController extends Controller
{
    public function index()
    {
        $data = RealisasiKpi::with([
            'ulp',
            'kpi',
            'periode'
        ])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data Realisasi KPI berhasil diambil',
            'data' => $data
        ]);
    }
}