<?php

use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::view('/login', 'app')->name('login');
Route::view('/register', 'app')->name('register');
Route::view('/forgot-password', 'app')->name('password.request');
Route::view('/reset-password/{token}', 'app')->name('password.reset');

Route::view('/{any?}', 'app')->where('any', '.*');
