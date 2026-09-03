<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\Integration\InventoryController;
use App\Http\Controllers\Api\Integration\RoomController as IntegrationRoomController;
use App\Http\Controllers\Api\Integration\RoomImageController as IntegrationRoomImageController;
use App\Http\Controllers\Api\Integration\PlanController as IntegrationPlanController;
use App\Http\Controllers\Api\Integration\ReservationController as IntegrationReservationController;
use App\Http\Controllers\Api\Integration\UserController as IntegrationUserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/rooms', [RoomController::class, 'index']);

Route::prefix('integration')
    ->middleware('integration.api')
    ->group(function () {
        Route::get('/rooms', [IntegrationRoomController::class, 'index']);
        Route::post('/rooms', [IntegrationRoomController::class, 'store']);
        Route::patch('/rooms/{room}', [IntegrationRoomController::class, 'update']);
        Route::delete('/rooms/{room}', [IntegrationRoomController::class, 'destroy']);
        Route::put('/rooms/{room}/images', [IntegrationRoomImageController::class, 'sync']);
        Route::get('/plans', [IntegrationPlanController::class, 'index']);
        Route::post('/plans', [IntegrationPlanController::class, 'store']);
        Route::patch('/plans/{plan}', [IntegrationPlanController::class, 'update']);
        Route::delete('/plans/{plan}', [IntegrationPlanController::class, 'destroy']);
        Route::get('/inventories', [InventoryController::class, 'index']);
        Route::patch('/inventories', [InventoryController::class, 'update']);
        Route::put('/pricing-settings', [\App\Http\Controllers\Api\Integration\PricingSettingsController::class, 'sync']);
        Route::get('/reservations', [IntegrationReservationController::class, 'index']);
        Route::post('/reservations', [IntegrationReservationController::class, 'store']);
        Route::patch('/reservations/{reservation}/cancel', [IntegrationReservationController::class, 'cancel']);
        Route::patch('/reservations/{reservation}/payment', [IntegrationReservationController::class, 'updatePayment']);
        Route::get('/users', [IntegrationUserController::class, 'index']);
    });