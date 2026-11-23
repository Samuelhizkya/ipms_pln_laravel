<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLoginInputMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (is_string($request->input('username'))) {
            $request->merge([
                'username' => trim($request->input('username')),
            ]);
        }

        $request->validate([
            'username' => [
                'required',
                'string',
                'max:50',
            ],
            'password' => [
                'required',
                'string',
                'max:4096',
            ],
        ]);

        return $next($request);
    }
}