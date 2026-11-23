<?php

namespace App\Http\Controllers;

use App\Support\WebLogin;
use Illuminate\Http\Request;

class UlpRankingController extends RankingController
{
    public function index(Request $request)
    {
        $user = WebLogin::resolve($request);

        abort_unless(
            $user && $user->role === 'ulp',
            403,
            'Halaman ini khusus akun ULP.'
        );

        abort_unless(
            $user->id_ulp,
            403,
            'Akun belum terhubung dengan ULP.'
        );

        // Menggunakan ringkasan seluruh ULP
        // dari controller ranking yang sudah ada.
        $rankingView = parent::index($request);

        $data = $rankingView->getData();

        // Untuk memberikan penanda "Unit Anda".
        $data['unitAndaId'] = (int) $user->id_ulp;

        return view('ulp.ranking', $data);
    }
}