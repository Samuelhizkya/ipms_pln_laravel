<?php

use App\Http\Controllers\Api\MobileKpiImportController;
use App\Http\Middleware\ApiAccessMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/kpi')
    ->middleware([
        'auth:sanctum',
        ApiAccessMiddleware::class . ':account',
    ])
    ->group(function () {
        Route::get(
            '/import-periods',
            [MobileKpiImportController::class, 'periods']
        );

        Route::post(
            '/import-target',
            [MobileKpiImportController::class, 'target']
        );

        Route::post(
            '/import-realisasi',
            [MobileKpiImportController::class, 'realisasi']
        );
    });