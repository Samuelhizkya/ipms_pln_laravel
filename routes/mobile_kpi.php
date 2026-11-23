<?php

use App\Http\Controllers\Api\MobileKpiEditController;
use App\Http\Middleware\ApiAccessMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/kpi')
    ->middleware([
        'auth:sanctum',
        ApiAccessMiddleware::class . ':account',
    ])
    ->group(function () {
        Route::get(
            '/target/{target}',
            [MobileKpiEditController::class, 'showTarget']
        )->whereNumber('target');

        Route::post(
            '/target/{target}',
            [MobileKpiEditController::class, 'updateTarget']
        )->whereNumber('target');

        Route::get(
            '/realisasi/{realisasi}',
            [MobileKpiEditController::class, 'showRealisasi']
        )->whereNumber('realisasi');

        Route::post(
            '/realisasi/{realisasi}',
            [MobileKpiEditController::class, 'updateRealisasi']
        )->whereNumber('realisasi');
    });