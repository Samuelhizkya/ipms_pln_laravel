<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UlpMonitoringController extends UlpDashboardController
{
    public function index(Request $request)
    {
        // Menggunakan pemeriksaan akun dan pembatasan ULP
        // yang sudah ada pada controller Dashboard ULP.
        $dashboardView = parent::index($request);

        // Data berisi target, realisasi, periode,
        // dan ringkasan milik ULP yang sedang login.
        $data = $dashboardView->getData();

        return view('ulp.monitoring', $data);
    }
}