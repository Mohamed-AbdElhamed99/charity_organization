<?php

use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LocaleController;
use App\Http\Controllers\Site\NewsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/lang/{locale}', LocaleController::class)->name('lang.switch');
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news:slug}', [NewsController::class, 'show'])->name('news.show');
