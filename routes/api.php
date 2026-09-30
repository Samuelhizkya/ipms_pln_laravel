<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UlpController;
use App\Http\Controllers\Api\MasterKpiController;
use App\Http\Controllers\Api\KpiUlpController;
use App\Http\Controllers\Api\TargetKpiController;
use App\Http\Controllers\Api\TargetKpiBulananController;
use App\Http\Controllers\Api\RealisasiKpiController;
use App\Http\Controllers\Api\AuthController;

Route::get('/ulp', [UlpController::class, 'index']);

Route::get('/master-kpi', [MasterKpiController::class, 'index']);

Route::get('/kpi-ulp', [KpiUlpController::class, 'index']);

Route::get('/target-kpi', [TargetKpiController::class, 'index']);

Route::get('/target-kpi-bulanan', [TargetKpiBulananController::class, 'index']);

Route::get('/realisasi-kpi', [RealisasiKpiController::class, 'index']);
Route::post('/login', [AuthController::class, 'login']);