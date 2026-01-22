<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserGraphController;
use App\Http\Controllers\Api\ChnagePasswordController;

// PUBLIC
Route::post('/register', [AuthController::class, 'register']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/change-password', [AuthController::class, 'changePassword']);

// FORGOT PASSWORD (Public routes)

// PROTECTED (Passport)
Route::middleware('auth:api')->group(function () {
    Route::post('/user-graph', [UserGraphController::class, 'userGraph']);
    Route::post('/users/list', [UserController::class, 'list']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::get('/hobbies', [UserController::class, 'getHobbies']);
    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/changepassword', [ChnagePasswordController::class, 'password']);
});