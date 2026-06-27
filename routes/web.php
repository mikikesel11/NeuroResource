<?php

use Illuminate\Support\Facades\Route;

// Public site
Route::view('/', 'home')->name('home');

// Nav destinations built incrementally — placeholders so links never 404.
Route::view('/shop', 'coming-soon', ['heading' => 'The Shop'])->name('shop');
Route::view('/blog', 'coming-soon', ['heading' => 'The Blog'])->name('blog');
Route::view('/resources', 'coming-soon', ['heading' => 'Resource Library'])->name('resources');
Route::view('/about', 'coming-soon', ['heading' => 'About'])->name('about');
Route::view('/play', 'coming-soon', ['heading' => 'The Adventure'])->name('play');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
