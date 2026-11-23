<?php

namespace App\Http\Middleware;

use App\Models\PeriodeKinerja;
use App\Models\Ulp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAccessMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string $scope = 'unit'
    ): Response {
        $user = $request->user();

        abort_unless(
            $user && $user->status === 'aktif',
            403,
            'Akun tidak tersedia atau tidak aktif.'
        );

        abort_unless(
            in_array($user->role, ['admin', 'ulp'], true),
            403,
            'Role akun tidak memiliki akses.'
        );

        /*
         * Mode account hanya memeriksa akun.
         * Dipakai untuk daftar unit dan Master KPI.
         */
        if ($scope === 'account') {
            return $next($request);
        }

        $validated = $request->validate([
            'id_ulp' => [
                $user->role === 'admin' ? 'required' : 'sometimes',
                'required',
                'integer',
                'min:1',
            ],
        ]);

        if ($user->role === 'ulp') {
            abort_unless(
                $user->id_ulp,
                403,
                'Akun belum memiliki ULP. Hubungi Admin.'
            );

            $unitId = (int) $user->id_ulp;

            if (isset($validated['id_ulp'])) {
                abort_unless(
                    (int) $validated['id_ulp'] === $unitId,
                    403,
                    'Anda tidak memiliki akses ke ULP tersebut.'
                );
            }
        } else {
            $unitId = (int) $validated['id_ulp'];
        }

        $unit = Ulp::query()
            ->where('id_ulp', $unitId)
            ->where('status', 'aktif')
            ->first();

        abort_unless(
            $unit,
            403,
            'ULP tidak tersedia atau tidak aktif.'
        );

        $request->merge(['id_ulp' => $unitId]);
        $request->attributes->set('account_ulp', $unit);

        /*
         * Dashboard lama mengelompokkan berdasarkan KPI.
         * Wajibkan satu periode agar beberapa bulan tidak tercampur.
         */
        if ($scope === 'dashboard') {
            $periodInput = $request->validate([
                'id_periode' => [
                    'required',
                    'integer',
                    'exists:periode_kinerja,id_periode',
                ],
                'tahun' => ['sometimes', 'required', 'integer'],
            ]);

            $periode = PeriodeKinerja::findOrFail(
                $periodInput['id_periode']
            );

            abort_if(
                isset($periodInput['tahun'])
                    && (int) $periodInput['tahun']
                        !== (int) $periode->tahun,
                422,
                'Tahun tidak sesuai dengan periode yang dipilih.'
            );

            $request->merge([
                'id_periode' => (int) $periode->id_periode,
                'tahun' => (int) $periode->tahun,
            ]);
        }

        return $next($request);
    }
}