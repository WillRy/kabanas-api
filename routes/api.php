<?php

use App\Http\Controllers\Api\AuthController;
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
    Route::post('/', [PropertyController::class, 'store']);
    Route::get('/', [PropertyController::class, 'index']);
    Route::post('/{property}', [PropertyController::class, 'update']);
    Route::delete('/{property}', [PropertyController::class, 'destroy']);
});


Route::group(['prefix' => 'setting', 'middleware' => ['auth:sanctum']], function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::put('/', [SettingController::class, 'update']);
});
