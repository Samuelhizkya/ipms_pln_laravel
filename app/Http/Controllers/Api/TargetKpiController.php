<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TargetKpi;

class TargetKpiController extends Controller
{
    public function index()
    {
        $data = TargetKpi::with([
            'ulp',
            'kpi',
            'targetBulanan'
        ])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data Target KPI berhasil diambil',
            'data' => $data
        ]);
    }
}