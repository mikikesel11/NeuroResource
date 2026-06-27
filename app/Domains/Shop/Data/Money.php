<?php

namespace App\Domains\Shop\Data;

/**
 * A money amount in a given currency, as returned by Shopify (decimal string).
 * Kept dependency-free (no ext-intl) for deterministic formatting in tests.
 */
final class Money
{
    private const SYMBOLS = [
        'USD' => '$',
        'CAD' => 'CA$',
        'AUD' => 'A$',
        'EUR' => '€',
        'GBP' => '£',
    ];

    public function __construct(
        public readonly string $amount,
        public readonly string $currencyCode,
    ) {}

    public function formatted(): string
    {
        $symbol = self::SYMBOLS[$this->currencyCode] ?? ($this->currencyCode.' ');

        return $symbol.number_format((float) $this->amount, 2);
    }
}
