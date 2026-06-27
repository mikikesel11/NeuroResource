<?php

namespace App\Providers;

use App\Domains\Shop\Catalog\FakeCatalog;
use App\Domains\Shop\Catalog\ShopifyCatalog;
use App\Domains\Shop\Contracts\ProductCatalog;
use App\Domains\Shop\Services\StorefrontClient;
use Illuminate\Support\ServiceProvider;

class ShopServiceProvider extends ServiceProvider
{
    /**
     * Bind the product catalog: the live Shopify implementation when a
     * Storefront token is configured, otherwise local fixtures. This lets the
     * Shop be developed and tested without a Shopify account.
     */
    public function register(): void
    {
        $this->app->singleton(ProductCatalog::class, function () {
            if (config('services.shopify.storefront_token')) {
                return new ShopifyCatalog(StorefrontClient::fromConfig());
            }

            return new FakeCatalog;
        });
    }
}
