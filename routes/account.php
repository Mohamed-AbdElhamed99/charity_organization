<?php

use App\Http\Controllers\Site\Account\AccountDonationController;
use App\Http\Controllers\Site\Account\AccountDonationSubscriptionController;
use App\Http\Controllers\Site\Account\AccountPaymentMethodController;
use App\Http\Controllers\Site\Account\AccountProfileController;
use App\Http\Controllers\Site\Auth\AuthenticatedDonorSessionController;
use App\Http\Controllers\Site\Auth\DonorPasswordResetLinkController;
use App\Http\Controllers\Site\Auth\NewDonorPasswordController;
use App\Http\Controllers\Site\Auth\RegisteredDonorController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('register', [RegisteredDonorController::class, 'create'])->name('register');
        Route::post('register', [RegisteredDonorController::class, 'store'])
            ->middleware('throttle:5,1');

        Route::get('login', [AuthenticatedDonorSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedDonorSessionController::class, 'store'])
            ->middleware('throttle:5,1');

        Route::get('forgot-password', [DonorPasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [DonorPasswordResetLinkController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('password.email');

        Route::get('reset-password/{token}', [NewDonorPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewDonorPasswordController::class, 'store'])->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthenticatedDonorSessionController::class, 'destroy'])->name('logout');

        Route::get('profile', [AccountProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [AccountProfileController::class, 'update'])->name('profile.update');

        Route::get('payment-methods', [AccountPaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('payment-methods/setup-intent', [AccountPaymentMethodController::class, 'createSetupIntent'])->name('payment-methods.setup-intent');
        Route::post('payment-methods', [AccountPaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::delete('payment-methods/{paymentMethod}', [AccountPaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
        Route::post('payment-methods/{paymentMethod}/default', [AccountPaymentMethodController::class, 'setDefault'])->name('payment-methods.default');

        Route::get('donations', [AccountDonationController::class, 'index'])->name('donations.index');

        Route::post('subscriptions/{subscription}/cancel', [AccountDonationSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    });
});
