<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UlpController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ulp', [UlpController::class, 'index']);