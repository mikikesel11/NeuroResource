<?php

namespace App\Domains\Shop\Data;

/**
 * Display shape for a product, decoupled from the source (Shopify or fixtures).
 * The Livewire pages and Blade views only ever depend on this — never on the
 * Storefront API response. See docs/system-design.md §3.1.
 */
final class ProductData
{
    /** @param  list<VariantData>  $variants */
    public function __construct(
        public readonly string $id,
        public readonly string $handle,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $imageUrl,
        public readonly ?string $imageAlt,
        public readonly Money $price,
        public readonly bool $available,
        public readonly array $variants = [],
    ) {}
}
