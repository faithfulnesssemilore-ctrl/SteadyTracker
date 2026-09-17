<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', fn () => redirect(config('app.frontend_url').'/login'))->name('login');
Route::get('/register', fn () => redirect(config('app.frontend_url').'/register'))->name('register');
Route::get('/forgot-password', fn () => redirect(config('app.frontend_url').'/forgot-password'))->name('password.request');
Route::get('/reset-password/{token}', fn (string $token) => redirect(config('app.frontend_url').'/reset-password?token='.$token))->name('password.reset');

Route::view('/{any?}', 'app')->where('any', '.*');
