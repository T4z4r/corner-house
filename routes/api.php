<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Website\BookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function (): void {
    Route::get('/availability', [BookingController::class, 'availability']);
    Route::post('/availability', [BookingController::class, 'availability']);
    Route::post('/booking/hold', [BookingController::class, 'createHold']);
    Route::post('/booking/calculate-price', [BookingController::class, 'calculatePrice']);
    Route::post('/chat', [ChatController::class, 'store'])->middleware('throttle:20,1');
    Route::post('/messages', [ChatController::class, 'message'])->middleware('throttle:10,1');
});
