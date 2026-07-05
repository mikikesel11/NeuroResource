<?php

declare(strict_types=1);

namespace App\Domains\Shop\Contracts;

use App\Domains\Shop\Data\ProductData;

/**
 * Source of product data for the Shop. Implemented by ShopifyCatalog (live
 * Storefront API) and FakeCatalog (local fixtures). The binding is chosen in
 * ShopServiceProvider based on whether Shopify credentials are configured, so
 * the Shop works fully in development without a Shopify account.
 */
interface ProductCatalog
{
    /** @return list<ProductData> */
    public function products(int $limit = 24): array;

    public function find(string $handle): ?ProductData;
}
