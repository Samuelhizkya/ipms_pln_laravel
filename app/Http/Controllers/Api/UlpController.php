<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ulp;

class UlpController extends Controller
{
    public function index()
    {
        $ulps = Ulp::where('status', 'aktif')
                   ->orderBy('nama_ulp')
                   ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data ULP berhasil diambil',
            'data' => $ulps
        ]);
    }
}