<?php

declare(strict_types=1);

namespace App\Domains\Shop\Catalog;

use App\Domains\Shop\Contracts\ProductCatalog;
use App\Domains\Shop\Data\Money;
use App\Domains\Shop\Data\ProductData;
use App\Domains\Shop\Data\VariantData;

/**
 * Product catalog backed by local fixtures (Catalog/products.php). The default
 * implementation when no Shopify store is connected, so the Shop is fully
 * buildable and testable without a Shopify account.
 */
class FakeCatalog implements ProductCatalog
{
    /** @var list<array<string,mixed>>|null */
    private ?array $fixtures = null;

    public function products(int $limit = 24): array
    {
        return array_map(
            fn (array $row) => $this->toProduct($row),
            array_slice($this->all(), 0, $limit),
        );
    }

    public function find(string $handle): ?ProductData
    {
        foreach ($this->all() as $row) {
            if ($row['handle'] === $handle) {
                return $this->toProduct($row);
            }
        }

        return null;
    }

    /** @return list<array<string,mixed>> */
    private function all(): array
    {
        return $this->fixtures ??= require __DIR__.'/products.php';
    }

    /** @param  array<string,mixed>  $row */
    private function toProduct(array $row): ProductData
    {
        $variants = array_map(
            fn (array $v) => new VariantData(
                id: $v['id'],
                title: $v['title'],
                price: new Money($v['amount'], $row['price']['currency']),
                available: $v['available'],
            ),
            $row['variants'] ?? [],
        );

        return new ProductData(
            id: $row['id'],
            handle: $row['handle'],
            title: $row['title'],
            description: $row['description'] ?? null,
            imageUrl: $row['imageUrl'] ?? null,
            imageAlt: $row['imageAlt'] ?? null,
            price: new Money($row['price']['amount'], $row['price']['currency']),
            available: $row['available'],
            variants: $variants,
        );
    }
}
