<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UlpLaporanController extends UlpDashboardController
{
    public function index(Request $request)
    {
        // Menggunakan pemeriksaan akun dan pembatasan data
        // sesuai ULP yang sedang login.
        $dashboardView = parent::index($request);

        $data = $dashboardView->getData();

        return view('ulp.laporan', $data);
    }
}