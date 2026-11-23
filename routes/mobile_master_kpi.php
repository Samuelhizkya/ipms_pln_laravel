<?php

use App\Http\Controllers\Api\MobileMasterKpiController;
use App\Http\Middleware\ApiAccessMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/kpi')
    ->middleware([
        'auth:sanctum',
        ApiAccessMiddleware::class . ':account',
    ])
    ->group(function () {
        Route::get(
            '/master',
            [MobileMasterKpiController::class, 'index']
        );

        Route::get(
            '/master-form',
            [MobileMasterKpiController::class, 'createForm']
        );

        Route::get(
            '/master/{kpi}',
            [MobileMasterKpiController::class, 'editForm']
        )->whereNumber('kpi');

        Route::post(
            '/master',
            [MobileMasterKpiController::class, 'store']
        );

        Route::post(
            '/master/{kpi}',
            [MobileMasterKpiController::class, 'update']
        )->whereNumber('kpi');
    });