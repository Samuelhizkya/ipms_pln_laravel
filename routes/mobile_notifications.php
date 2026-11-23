<?php

use App\Http\Controllers\Api\MobileAccountNotificationController;
use App\Http\Middleware\ApiAccessMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    ApiAccessMiddleware::class . ':account',
    'throttle:60,1',
])->group(function () {
    Route::get(
        '/mobile/account/notifications',
        [MobileAccountNotificationController::class, 'index']
    );

    Route::post(
        '/mobile/account/notifications/{notification}/read',
        [MobileAccountNotificationController::class, 'markRead']
    )->whereUuid('notification');
});