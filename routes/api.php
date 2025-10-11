<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::any('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/start-password-reset', [AuthController::class, 'sendPasswordReset']);
    Route::post('/password-reset', [AuthController::class, 'passwordReset']);
});
