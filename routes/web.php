<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'welcome')->name('home');

// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::inertia('dashboard', 'dashboard')->name('dashboard');

//     Route::prefix('admin')->name('admin.')->group(function () {
//         Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
//         Route::post('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
//         Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
//     });
// });

// require __DIR__.'/settings.php';
