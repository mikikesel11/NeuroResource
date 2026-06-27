<?php

namespace App\Domains\Shop\Catalog;

use App\Domains\Shop\Contracts\ProductCatalog;
use App\Domains\Shop\Data\Money;
use App\Domains\Shop\Data\ProductData;
use App\Domains\Shop\Data\VariantData;
use App\Domains\Shop\Services\StorefrontClient;

/**
 * Product catalog backed by the live Shopify Storefront API. Reads are cached
 * via StorefrontClient and meant to be invalidated by Shopify webhooks. Maps
 * the GraphQL response into ProductData so the UI never touches the raw API.
 */
class ShopifyCatalog implements ProductCatalog
{
    private const PRODUCT_FIELDS = <<<'GRAPHQL'
        id
        handle
        title
        description
        availableForSale
        featuredImage { url altText }
        priceRange { minVariantPrice { amount currencyCode } }
        variants(first: 100) {
            nodes { id title availableForSale price { amount currencyCode } }
        }
    GRAPHQL;

    public function __construct(private readonly StorefrontClient $client) {}

    public function products(int $limit = 24): array
    {
        $fields = self::PRODUCT_FIELDS;
        $query = <<<GRAPHQL
            query Products(\$first: Int!) {
                products(first: \$first) { nodes { $fields } }
            }
        GRAPHQL;

        $data = $this->client->query($query, ['first' => $limit], cacheKey: "products:$limit");

        return array_map(
            fn (array $node) => $this->toProduct($node),
            $data['products']['nodes'] ?? [],
        );
    }

    public function find(string $handle): ?ProductData
    {
        $fields = self::PRODUCT_FIELDS;
        $query = <<<GRAPHQL
            query Product(\$handle: String!) {
                product(handle: \$handle) { $fields }
            }
        GRAPHQL;

        $data = $this->client->query($query, ['handle' => $handle], cacheKey: "product:$handle");

        $node = $data['product'] ?? null;

        return $node ? $this->toProduct($node) : null;
    }

    /** @param  array<string,mixed>  $node */
    private function toProduct(array $node): ProductData
    {
        $min = $node['priceRange']['minVariantPrice'] ?? ['amount' => '0', 'currencyCode' => 'USD'];

        $variants = array_map(
            fn (array $v) => new VariantData(
                id: $v['id'],
                title: $v['title'],
                price: new Money($v['price']['amount'], $v['price']['currencyCode']),
                available: (bool) $v['availableForSale'],
            ),
            $node['variants']['nodes'] ?? [],
        );

        return new ProductData(
            id: $node['id'],
            handle: $node['handle'],
            title: $node['title'],
            description: $node['description'] ?? null,
            imageUrl: $node['featuredImage']['url'] ?? null,
            imageAlt: $node['featuredImage']['altText'] ?? null,
            price: new Money($min['amount'], $min['currencyCode']),
            available: (bool) ($node['availableForSale'] ?? false),
            variants: $variants,
        );
    }
}
