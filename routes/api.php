<?php

use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/notifications', [NotificationController::class, 'send']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{externalId}', [NotificationController::class, 'show']);
});
