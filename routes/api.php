<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MasterKpiController;
use App\Http\Controllers\Api\RealisasiKpiController;
use App\Http\Controllers\Api\ScopedDataController;
use App\Http\Controllers\Api\TargetKpiController;
use App\Http\Middleware\ApiAccessMiddleware;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware([
        'throttle:5,1',
        \App\Http\Middleware\ApiLoginInputMiddleware::class,
    ]);

Route::middleware([
    'auth:sanctum',
    ApiAccessMiddleware::class . ':account',
])->group(function () {
    Route::get('/ulp', [ScopedDataController::class, 'units']);

    Route::get(
        '/master-kpi',
        [MasterKpiController::class, 'index']
    );
});

Route::middleware([
    'auth:sanctum',
    ApiAccessMiddleware::class,
])->group(function () {
    Route::get(
        '/kpi-ulp',
        [ScopedDataController::class, 'kpiUlp']
    );

    Route::get(
        '/target-kpi',
        [TargetKpiController::class, 'index']
    );

    Route::get(
        '/target-kpi-bulanan',
        [ScopedDataController::class, 'monthlyTargets']
    );

    Route::get(
        '/realisasi-kpi',
        [RealisasiKpiController::class, 'index']
    );
});

Route::get(
    '/dashboard',
    [DashboardController::class, 'index']
)->middleware([
    'auth:sanctum',
    ApiAccessMiddleware::class . ':dashboard',
]);
Route::post('/logout', function (
    \Illuminate\Http\Request $request
) {
    $token = $request->user()->currentAccessToken();

    abort_unless(
        $token instanceof \Laravel\Sanctum\PersonalAccessToken,
        400,
        'Gunakan token API untuk endpoint logout ini.'
    );

    $token->delete();

    return response()->json([
        'success' => true,
        'message' => 'Token API telah dicabut.',
    ]);
})->middleware('auth:sanctum');

require __DIR__ . '/mobile_session.php';
require __DIR__ . '/mobile_kpi.php';
require __DIR__ . '/mobile_master_kpi.php';
require __DIR__ . '/mobile_kpi_import.php';
require __DIR__ . '/mobile_notifications.php';
Route::get('/ranking-ulp', function (
    \Illuminate\Http\Request $request
) {
    $request->validate([
        'bulan' => ['required', 'integer', 'between:1,12'],
        'tahun' => ['required', 'integer', 'between:2000,2100'],
    ]);

    abort_if(
        $request->user()->role === 'ulp'
            && !$request->user()->id_ulp,
        403,
        'Akun belum terhubung dengan ULP.'
    );

    $request->query->set('dashboard_nko', '1');

    return app(
        \App\Http\Controllers\RankingController::class
    )->index($request);
})->middleware([
    'auth:sanctum',
    ApiAccessMiddleware::class . ':account',
    'throttle:30,1',
]);