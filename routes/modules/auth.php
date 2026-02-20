<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\User\ProfileController;

Route::middleware('guest')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('sign-in/social', [AuthController::class, 'socialSignIn']);
    Route::post('signup/verify', [AuthController::class, 'verifySignup']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('forgot-password/verify', [AuthController::class, 'verifyForgotPassword']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::get('otp-purposes', [AuthController::class, 'otpPurposes']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'getMe']);
    Route::get('logout', [AuthController::class, 'logout']);
    Route::post('business-profile', [ProfileController::class, 'businessProfile']);
    Route::patch('profile', [ProfileController::class, 'updateProfile']);
    Route::patch('profile/cover-photo', [ProfileController::class, 'updateCoverPhoto']);
    Route::patch('profile/email', [ProfileController::class, 'updateEmail']);
    Route::patch('profile/email/verify', [ProfileController::class, 'updateEmail']);
    Route::patch('profile/password', [ProfileController::class, 'updatePassword']);
    Route::delete('profile/delete', [ProfileController::class, 'destroy']);
});
