<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ulp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // =========================
        // AKUN ADMIN
        // =========================
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'id_ulp' => null,
                'password' => Hash::make('Admin@123'),
                'nama_lengkap' => 'Administrator IPMS PLN',
                'role' => 'admin',
                'status' => 'aktif',
            ]
        );

        // =========================
        // AKUN USER ULP
        // =========================
        $ulps = Ulp::where('status', 'aktif')->get();

        foreach ($ulps as $ulp) {
            User::updateOrCreate(
                ['username' => strtolower($ulp->kode_ulp)],
                [
                    'id_ulp' => $ulp->id_ulp,
                    'password' => Hash::make('Ulp@123'),
                    'nama_lengkap' => $ulp->nama_ulp,
                    'role' => 'ulp',
                    'status' => 'aktif',
                ]
            );
        }
    }
}