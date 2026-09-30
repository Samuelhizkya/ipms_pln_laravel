<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TargetKpiBulanan;

class TargetKpiBulananController extends Controller
{
    public function index()
    {
        $data = TargetKpiBulanan::with([
            'targetKpi.ulp',
            'targetKpi.kpi'
        ])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data Target KPI Bulanan berhasil diambil',
            'data' => $data
        ]);
    }
}