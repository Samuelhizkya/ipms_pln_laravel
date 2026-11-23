<?php

namespace App\Http\Middleware;

use App\Support\WebLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = WebLogin::resolve($request);

        if (!$user) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()
                    ->json([
                        'message' => 'Sesi berakhir. Silakan login kembali.',
                    ], 401)
                    ->header('Cache-Control', 'no-store, private');
            }

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Silakan login kembali. Sesi berakhir atau akun tidak aktif.'
                )
                ->header('Cache-Control', 'no-store, private');
        }

        $response = $next($request);

        $response->headers->set(
            'Cache-Control',
            'no-store, private'
        );

        return $response;
    }
}