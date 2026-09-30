<?php

namespace App\Http\Controllers;

use App\Models\Ulp;

class UlpController extends Controller
{
    public function index()
    {
        $ulps = Ulp::where('status', 'aktif')
                   ->orderBy('nama_ulp')
                   ->get();

        return view('ulp.index', compact('ulps'));
    }
}