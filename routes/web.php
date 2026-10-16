<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UlpController;
use App\Http\Controllers\TargetKpiController;
use App\Http\Controllers\RealisasiKpiController;



Route::get('/', function () {
    return view('welcome');
});

Route::get('/ulp', [UlpController::class, 'index']);
Route::get('/target-kpi/import', [TargetKpiController::class, 'create'])
    ->name('target-kpi.import.form');
Route::post('/target-kpi/import', [TargetKpiController::class, 'import'])
    ->name('target-kpi.import');
    Route::get('/realisasi-kpi/import', [RealisasiKpiController::class, 'create'])
    ->name('realisasi-kpi.import.form');
Route::get('/realisasi-kpi/import', [RealisasiKpiController::class, 'create'])
    ->name('realisasi-kpi.import.form');

Route::post('/realisasi-kpi/import', [RealisasiKpiController::class, 'import'])
    ->name('realisasi-kpi.import');