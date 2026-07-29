<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:web')->group(function () {
    // Les anciens formulaires Breeze sont supprimes.
    // Chaque portail a son propre login/register (vendor, influencer, admin).
    Route::get('login', fn () => redirect()->route('home'))->name('login');
    Route::get('register', function (\Illuminate\Http\Request $request) {
        $ref = $request->query('ref');
        return redirect()->route('home', $ref ? ['ref' => $ref] : []);
    })->name('register');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth:web')->group(function () {
    Route::get('verify-email', [EmailVerificationCodeController::class, 'show'])
        ->name('verification.notice');

    Route::post('email/verification-notification', [EmailVerificationCodeController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('verify-email-code', [EmailVerificationCodeController::class, 'verify'])
        ->middleware('throttle:6,1')
        ->name('verification.verify');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
