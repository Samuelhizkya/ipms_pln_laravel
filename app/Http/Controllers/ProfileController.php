<?php

namespace App\Http\Controllers;

use App\Models\Ulp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        // Identitas diambil dari session yang sudah diperiksa middleware.
        // Tidak menerima ID pengguna dari URL atau form.
        $account = User::query()
            ->select([
                'id_user',
                'id_ulp',
                'username',
                'nama_lengkap',
                'role',
                'status',
            ])
            ->where(
                'id_user',
                $request->session()->get('user.id_user')
            )
            ->where('status', 'aktif')
            ->firstOrFail();

        $unitName = $account->id_ulp !== null
            ? Ulp::query()
                ->where('id_ulp', $account->id_ulp)
                ->value('nama_ulp')
            : null;

        return view('users.profile', [
            'account' => $account,
            'unitName' => $unitName,
        ]);
    }
}