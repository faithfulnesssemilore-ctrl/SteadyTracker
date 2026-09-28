<?php

use Illuminate\Support\Facades\Route;

Route::view('/login', 'app')->name('login');
Route::view('/register', 'app')->name('register');
Route::view('/forgot-password', 'app')->name('password.request');
Route::view('/reset-password/{token}', 'app')->name('password.reset');

Route::view('/{any?}', 'app')->where('any', '.*');
