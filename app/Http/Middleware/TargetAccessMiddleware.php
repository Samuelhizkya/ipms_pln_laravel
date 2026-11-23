<?php

namespace App\Http\Middleware;

use App\Models\Ulp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TargetAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->session()->get('user.role');

        if ($role === 'ulp') {
            return app(UlpAccessMiddleware::class)->handle(
                $request,
                $next
            );
        }

        abort_unless(
            $role === 'admin',
            403,
            'Anda tidak memiliki akses ke data target KPI.'
        );

        $validated = $request->validate([
            'id_ulp' => ['required', 'integer', 'min:1'],
        ]);

        $unit = Ulp::query()
            ->where('id_ulp', $validated['id_ulp'])
            ->where('status', 'aktif')
            ->first();

        abort_unless(
            $unit,
            422,
            'ULP yang dipilih tidak tersedia atau tidak aktif.'
        );

        $request->merge([
            'id_ulp' => (int) $unit->id_ulp,
        ]);

        $request->attributes->set('account_ulp', $unit);

        return $next($request);
    }
}