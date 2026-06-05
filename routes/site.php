<?php

use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/lang/{locale}', LocaleController::class)->name('lang.switch');
