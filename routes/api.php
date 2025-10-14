<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::any('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/start-password-reset', [AuthController::class, 'sendPasswordReset']);
    Route::post('/password-reset', [AuthController::class, 'passwordReset']);
});

Route::group(['prefix' => 'property', 'middleware' => ['auth:sanctum']], function () {
    Route::get('/', [PropertyController::class, 'index']);
    Route::post('/', [PropertyController::class, 'store']);
    Route::post('/{property}', [PropertyController::class, 'update']);
    Route::delete('/{property}', [PropertyController::class, 'destroy']);
});

Route::group(['prefix' => 'bookings', 'middleware' => ['auth:sanctum']], function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::put('/{booking}/check-in', [BookingController::class, 'checkIn']);
    Route::put('/{booking}/check-out', [BookingController::class, 'checkOut']);
    Route::delete('/{booking}', [BookingController::class, 'destroy']);
    Route::get('/stats', [BookingController::class, 'stats']);
});

Route::group(['prefix' => 'setting', 'middleware' => ['auth:sanctum']], function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::put('/', [SettingController::class, 'update']);
});
