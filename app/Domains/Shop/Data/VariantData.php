<?php

declare(strict_types=1);

namespace App\Domains\Shop\Data;

final class VariantData
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly Money $price,
        public readonly bool $available,
    ) {}
}
