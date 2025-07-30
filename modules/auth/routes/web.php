<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Auth\Http\Controllers\ConfirmablePasswordController;
use Modules\Auth\Http\Controllers\EmailVerificationNotificationController;
use Modules\Auth\Http\Controllers\EmailVerificationPromptController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\NewPasswordController;
use Modules\Auth\Http\Controllers\PasskeyAuthController;
use Modules\Auth\Http\Controllers\PasskeyController;
use Modules\Auth\Http\Controllers\PasswordController;
use Modules\Auth\Http\Controllers\PasswordResetLinkController;
use Modules\Auth\Http\Controllers\ProfileController;
use Modules\Auth\Http\Controllers\RegisterController;
use Modules\Auth\Http\Controllers\TwoFactorController;
use Modules\Auth\Http\Controllers\VerifyEmailController;

Route::middleware(['web', 'guest'])->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    // User Status Check (email verification + passkeys)
    Route::post('check-user-status', [LoginController::class, 'checkUserStatus'])->name('login.check-status');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Passkey Authentication
    Route::post('passkey/authentication-options', [PasskeyAuthController::class, 'authenticationOptions'])->name('passkey.auth.options');
    Route::post('passkey/authenticate', [PasskeyAuthController::class, 'authenticate'])->name('passkey.auth.verify');
    Route::post('passkey/check-user', [PasskeyAuthController::class, 'checkUserPasskeys'])->name('passkey.check-user');

    // Two-Factor Authentication Challenge (for guest users during login)
    Route::get('two-factor-challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('two-factor-challenge', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])->name('password.confirm.store');
    Route::get('password/confirmation', [ConfirmablePasswordController::class, 'status'])->name('password.confirmation');

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('auth::settings/appearance');
    })->name('appearance');

    // Two-Factor Authentication Management
    Route::prefix('user/two-factor')->name('two-factor.')->group(function () {
        Route::post('enable', [TwoFactorController::class, 'enable'])->name('enable');
        Route::get('qr-code', [TwoFactorController::class, 'qrCode'])->name('qr-code');
        Route::get('secret-key', [TwoFactorController::class, 'secretKey'])->name('secret-key');
        Route::get('recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('recovery-codes');
        Route::post('recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes.regenerate');
        Route::post('confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::delete('disable', [TwoFactorController::class, 'disable'])->name('disable');
    });

    // Passkey Management
    Route::prefix('user/passkeys')->name('passkeys.')->group(function () {
        Route::get('/', [PasskeyController::class, 'index'])->name('index');
        Route::post('registration-options', [PasskeyController::class, 'registrationOptions'])->name('registration.options');
        Route::post('register', [PasskeyController::class, 'store'])->name('store');
        Route::put('{passkey}', [PasskeyController::class, 'update'])->name('update');
        Route::delete('{passkey}', [PasskeyController::class, 'destroy'])->name('destroy');
    });
});
