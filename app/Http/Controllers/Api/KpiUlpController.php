<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KpiUlp;

class KpiUlpController extends Controller
{
    public function index()
    {
        $data = KpiUlp::with(['kpi', 'ulp'])
                       ->where('status', 'aktif')
                       ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data KPI ULP berhasil diambil',
            'data' => $data
        ]);
    }
}