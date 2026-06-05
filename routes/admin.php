<?php

use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::inertia('/', 'dashboard')->name('dashboard');
        Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::post('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
        Route::post('news/bulk-destroy', [NewsController::class, 'bulkDestroy'])->name('news.bulk-destroy');
        Route::post('news/{id}/restore', [NewsController::class, 'restore'])->name('news.restore');
        Route::resource('news', NewsController::class)->except(['show', 'create', 'edit']);
    });
});

require __DIR__.'/settings.php';
