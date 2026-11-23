<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UlpStatistikController extends UlpDashboardController
{
    public function index(Request $request)
    {
        // Memakai pemeriksaan akun dan data unit sendiri
        // yang sudah tersedia pada Dashboard ULP.
        $dashboardView = parent::index($request);

        $data = $dashboardView->getData();

        // Menggunakan pencapaian yang tersimpan.
        // Nilai kosong tidak dimasukkan ke rata-rata.
        $assessed = $data['actuals']->filter(
            fn ($row) => $row->pencapaian !== null
        );

        $data['assessedCount'] = $assessed->count();

        $data['averageAchievement'] = $assessed->isEmpty()
            ? null
            : $assessed->avg('pencapaian');

        return view('ulp.statistik', $data);
    }
}