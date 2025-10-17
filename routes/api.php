<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/user', [AuthController::class, 'user'])->middleware('auth:api,sanctum');
Route::any('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/start-password-reset', [AuthController::class, 'sendPasswordReset']);
    Route::post('/password-reset', [AuthController::class, 'passwordReset']);
    Route::post('/refresh', [AuthController::class, 'refreshToken']);
});

Route::group(['prefix' => 'property', 'middleware' => ['auth:api,sanctum']], function () {
    Route::get('/', [PropertyController::class, 'index']);
    Route::post('/', [PropertyController::class, 'store']);
    Route::post('/{property}', [PropertyController::class, 'update']);
    Route::delete('/{property}', [PropertyController::class, 'destroy']);
});

Route::group(['prefix' => 'bookings', 'middleware' => ['auth:api,sanctum']], function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::get('/stats', [BookingController::class, 'stats']);
    Route::get('/today-activity', [BookingController::class, 'todayActivity']);
    Route::get('/{booking}', [BookingController::class, 'view']);
    Route::put('/{booking}/check-in', [BookingController::class, 'checkIn']);
    Route::put('/{booking}/check-out', [BookingController::class, 'checkOut']);
    Route::delete('/{booking}', [BookingController::class, 'destroy']);

});

Route::group(['prefix' => 'setting', 'middleware' => ['auth:api,sanctum']], function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::put('/', [SettingController::class, 'update']);
});
Route::group(['prefix' => 'profile', 'middleware' => ['auth:api,sanctum']], function () {
    Route::post('/', [ProfileController::class, 'update']);
});
