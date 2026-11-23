<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('user.id_user');

        $user = $userId
            ? User::query()->find($userId)
            : null;

        abort_unless(
            $user &&
            $user->status === 'aktif' &&
            $user->role === 'admin',
            403,
            'Manajemen User hanya dapat diakses oleh Admin.'
        );

        return $next($request);
    }
}