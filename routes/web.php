<?php

use App\Domains\Profile\Http\Controllers\AboutController;
use App\Domains\Resources\Http\Controllers\DownloadController;
use App\Livewire\Resources\Library;
use App\Livewire\Resources\ResourcePage;
use App\Livewire\Shop\Catalog;
use App\Livewire\Shop\ProductPage;
use Illuminate\Support\Facades\Route;

// Public site
Route::view('/', 'home')->name('home');
Route::get('/about', AboutController::class)->name('about');

// Shop (headless Shopify — see ProductCatalog binding)
Route::get('/shop', Catalog::class)->name('shop');
Route::get('/shop/{handle}', ProductPage::class)->name('shop.product');

// Resource Library (free + email-gated downloads — see ResourceGate)
Route::get('/resources', Library::class)->name('resources');
Route::get('/resources/{slug}', ResourcePage::class)->name('resources.show');
Route::get('/resources/{slug}/download', DownloadController::class)->name('resources.download');

// Nav destinations built incrementally — placeholders so links never 404.
Route::view('/blog', 'coming-soon', ['heading' => 'The Blog'])->name('blog');
Route::view('/play', 'coming-soon', ['heading' => 'The Adventure'])->name('play');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
