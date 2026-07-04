<?php

use App\Domains\Game\Http\Controllers\AdventureController;
use App\Domains\Game\Http\Controllers\GameProgressController;
use App\Domains\Profile\Http\Controllers\AboutController;
use App\Domains\Resources\Http\Controllers\ConfirmUnlockController;
use App\Domains\Resources\Http\Controllers\DownloadController;
use App\Livewire\Actions\Logout;
use App\Livewire\Blog\Index as BlogIndex;
use App\Livewire\Blog\Show as BlogShow;
use App\Livewire\Resources\Library;
use App\Livewire\Resources\ResourcePage;
use App\Livewire\Shop\Catalog;
use App\Livewire\Shop\ProductPage;
use Illuminate\Support\Facades\Route;

/*
| The main site (everything except the Adventure game).
*/
$mainRoutes = function (): void {
    Route::view('/', 'home')->name('home');

    // Logout from the signed-in banner (Breeze handles it via a Livewire action
    // elsewhere; this gives the public layout a simple POST target).
    Route::post('/logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');

    Route::get('/about', AboutController::class)->name('about');

    // Shop (headless Shopify — see ProductCatalog binding)
    Route::get('/shop', Catalog::class)->name('shop');
    Route::get('/shop/{handle}', ProductPage::class)->name('shop.product');

    // Resource Library (free + email-gated downloads — see ResourceGate)
    Route::get('/resources', Library::class)->name('resources');
    // Two-segment routes before the catch-all /resources/{slug}.
    Route::get('/resources/confirm/{token}', ConfirmUnlockController::class)->name('resources.confirm');
    Route::get('/resources/{slug}', ResourcePage::class)->name('resources.show');
    Route::get('/resources/{slug}/download', DownloadController::class)->name('resources.download');

    // Blog. Register the RSS feed (route name "feeds.blog") before the catch-all
    // post route so /blog/feed isn't captured as a post slug.
    Route::get('/blog', BlogIndex::class)->name('blog');
    Route::feeds();
    Route::get('/blog/{slug}', BlogShow::class)->name('blog.show');

    Route::view('dashboard', 'dashboard')
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    Route::view('profile', 'profile')
        ->middleware(['auth'])
        ->name('profile');

    require __DIR__.'/auth.php';
};

/*
| The Adventure game + its cross-device save API. $base is '' on the play
| subdomain (game at the root) or '/play' in single-host mode.
*/
$adventureRoutes = function (string $base): void {
    Route::get($base === '' ? '/' : $base, AdventureController::class)->name('play');

    Route::middleware('auth')->group(function () use ($base) {
        Route::get($base.'/progress', [GameProgressController::class, 'show'])->name('play.progress.show');
        Route::post($base.'/progress', [GameProgressController::class, 'store'])->name('play.progress.store');
    });
};

$playDomain = config('neuroresource.domains.play');
$primaryDomain = config('neuroresource.domains.primary');

if ($playDomain) {
    // Split deployment: game on its own subdomain, the rest on the primary host.
    // Domain constraints also make route() generate the correct host per side.
    Route::domain($playDomain)->group(fn () => $adventureRoutes(''));
    Route::domain($primaryDomain)->group($mainRoutes);
} else {
    // Single host (local / CI): game at /play.
    $adventureRoutes('/play');
    $mainRoutes();
}
