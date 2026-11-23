<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiAccessMiddleware;

Route::get('/me', function (Request $request) {
    $user = $request->user();

    if ($user->role === 'ulp') {
        $ulpAktif = $user->id_ulp
            && \App\Models\Ulp::query()
                ->where('id_ulp', $user->id_ulp)
                ->where('status', 'aktif')
                ->exists();

        abort_unless(
            $ulpAktif,
            403,
            'ULP akun tidak tersedia atau tidak aktif.'
        );
    }

    return response()->json([
        'success' => true,
        'data' => $user->only([
            'id_user',
            'id_ulp',
            'username',
            'nama_lengkap',
            'role',
            'status',
        ]),
    ]);
})->middleware([
    'auth:sanctum',
    ApiAccessMiddleware::class . ':account',
]);
Route::middleware([
    'auth:sanctum',
    ApiAccessMiddleware::class . ':account',
    'throttle:5,1',
])->group(function () {
    Route::post(
        '/mobile/account/profile',
        [\App\Http\Controllers\Api\MobileAccountController::class, 'updateProfile']
    );

    Route::post(
        '/mobile/account/password',
        [\App\Http\Controllers\Api\MobileAccountController::class, 'updatePassword']
    );
});