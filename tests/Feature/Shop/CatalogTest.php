<?php

namespace Tests\Feature\Shop;

use App\Domains\Shop\Catalog\FakeCatalog;
use App\Domains\Shop\Catalog\ShopifyCatalog;
use App\Domains\Shop\Contracts\ProductCatalog;
use App\Domains\Shop\Services\StorefrontClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    public function test_fake_catalog_returns_products_and_finds_by_handle(): void
    {
        $catalog = new FakeCatalog;

        $this->assertNotEmpty($catalog->products());

        $product = $catalog->find('visual-timer');
        $this->assertNotNull($product);
        $this->assertSame('Visual Focus Timer', $product->title);

        $this->assertNull($catalog->find('does-not-exist'));
    }

    public function test_binding_uses_fake_catalog_when_no_shopify_token(): void
    {
        config(['services.shopify.storefront_token' => null]);

        $this->assertInstanceOf(FakeCatalog::class, $this->app->make(ProductCatalog::class));
    }

    public function test_binding_uses_shopify_catalog_when_token_present(): void
    {
        config([
            'services.shopify.storefront_domain' => 'neuroscouts.myshopify.com',
            'services.shopify.storefront_token' => 'test-token',
        ]);

        $this->assertInstanceOf(ShopifyCatalog::class, $this->app->make(ProductCatalog::class));
    }

    public function test_shopify_catalog_maps_storefront_response(): void
    {
        config([
            'services.shopify.storefront_domain' => 'neuroscouts.myshopify.com',
            'services.shopify.storefront_token' => 'test-token',
        ]);

        $node = [
            'id' => 'gid://shopify/Product/1',
            'handle' => 'weighted-lap-pad',
            'title' => 'Weighted Lap Pad',
            'description' => 'Grounding pressure.',
            'availableForSale' => true,
            'featuredImage' => ['url' => 'https://cdn.shopify.test/pad.jpg', 'altText' => 'A lap pad'],
            'priceRange' => ['minVariantPrice' => ['amount' => '39.00', 'currencyCode' => 'USD']],
            'variants' => ['nodes' => [
                ['id' => 'v1', 'title' => '2 kg', 'availableForSale' => true, 'price' => ['amount' => '39.00', 'currencyCode' => 'USD']],
                ['id' => 'v2', 'title' => '3 kg', 'availableForSale' => false, 'price' => ['amount' => '45.00', 'currencyCode' => 'USD']],
            ]],
        ];

        Http::fake(function ($request) use ($node) {
            $body = $request->data();
            if (str_contains($body['query'], 'products(first')) {
                return Http::response(['data' => ['products' => ['nodes' => [$node]]]]);
            }

            $handle = $body['variables']['handle'] ?? null;

            return Http::response(['data' => ['product' => $handle === 'weighted-lap-pad' ? $node : null]]);
        });

        $catalog = new ShopifyCatalog(StorefrontClient::fromConfig());

        $products = $catalog->products();
        $this->assertCount(1, $products);
        $p = $products[0];
        $this->assertSame('Weighted Lap Pad', $p->title);
        $this->assertSame('$39.00', $p->price->formatted());
        $this->assertSame('https://cdn.shopify.test/pad.jpg', $p->imageUrl);
        $this->assertTrue($p->available);
        $this->assertCount(2, $p->variants);
        $this->assertFalse($p->variants[1]->available);

        $this->assertSame('Weighted Lap Pad', $catalog->find('weighted-lap-pad')?->title);
        $this->assertNull($catalog->find('missing-handle'));

        // The request actually hit the Storefront endpoint with the auth header.
        Http::assertSent(fn ($request) => $request->hasHeader('X-Shopify-Storefront-Access-Token', 'test-token')
            && str_contains($request->url(), 'neuroscouts.myshopify.com/api/'));
    }
}
