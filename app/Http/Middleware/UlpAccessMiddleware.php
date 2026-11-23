<?php

namespace App\Http\Middleware;

use App\Models\Ulp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UlpAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
    $request->session()->get('user.role') === 'ulp',
    403,
    'Akses ini hanya tersedia untuk akun ULP.'
);
        $unitId = $request->session()->get('user.id_ulp');

        abort_unless(
            $unitId,
            403,
            'Akun belum memiliki ULP. Hubungi administrator IPMS PLN.'
        );

        $unit = Ulp::query()
            ->where('id_ulp', $unitId)
            ->where('status', 'aktif')
            ->first();

        abort_unless(
            $unit,
            403,
            'ULP akun tidak tersedia atau tidak aktif.'
        );

        $validated = $request->validate([
            'id_ulp' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        if (isset($validated['id_ulp'])) {
            abort_unless(
                (int) $validated['id_ulp'] === (int) $unit->id_ulp,
                403,
                'Anda tidak memiliki akses ke ULP tersebut.'
            );
        }

        $request->merge([
            'id_ulp' => (int) $unit->id_ulp,
        ]);

        $request->attributes->set('account_ulp', $unit);

        return $next($request);
    }
}