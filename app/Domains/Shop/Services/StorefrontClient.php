<?php

declare(strict_types=1);

namespace App\Domains\Shop\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin client for the Shopify Storefront GraphQL API (headless catalog + cart).
 *
 * We render products and carts natively on neuroresource.org for full control over
 * accessibility and markup, then hand off to Shopify's hosted checkout for
 * payment — so no PCI scope touches our servers. See docs/system-design.md §3.1.
 *
 * Catalog reads are cached and meant to be invalidated by Shopify webhooks
 * (products/update, collections/update) hitting a webhook controller. Cart
 * mutations are never cached.
 */
class StorefrontClient
{
    public function __construct(
        private readonly string $domain,
        private readonly string $token,
        private readonly string $apiVersion,
        private readonly int $cacheTtl,
    ) {}

    public static function fromConfig(): self
    {
        $domain = (string) config('services.shopify.storefront_domain');
        $token = (string) config('services.shopify.storefront_token');

        if ($domain === '' || $token === '') {
            throw new RuntimeException(
                'Shopify Storefront is not configured. Set SHOPIFY_STOREFRONT_DOMAIN and SHOPIFY_STOREFRONT_TOKEN.'
            );
        }

        return new self(
            domain: $domain,
            token: $token,
            apiVersion: (string) config('services.shopify.api_version', '2025-07'),
            cacheTtl: (int) config('services.shopify.cache_ttl', 600),
        );
    }

    /**
     * Run a Storefront GraphQL query. Read queries may be cached; pass a
     * non-null $cacheKey to enable caching. Mutations must omit it.
     *
     * @param  array<string,mixed>  $variables
     * @return array<string,mixed>
     */
    public function query(string $query, array $variables = [], ?string $cacheKey = null): array
    {
        $run = fn (): array => $this->execute($query, $variables);

        if ($cacheKey !== null) {
            return Cache::remember("shopify:$cacheKey", $this->cacheTtl, $run);
        }

        return $run();
    }

    /** Forget a cached read — call from the Shopify webhook handler. */
    public function forget(string $cacheKey): void
    {
        Cache::forget("shopify:$cacheKey");
    }

    /**
     * @param  array<string,mixed>  $variables
     * @return array<string,mixed>
     */
    private function execute(string $query, array $variables): array
    {
        $response = $this->request()->post('', [
            'query' => $query,
            // A non-empty assoc array already encodes as a JSON object; only an
            // empty one needs coercing so GraphQL receives {} rather than [].
            'variables' => $variables === [] ? new \stdClass : $variables,
        ]);

        $response->throw();

        $body = $response->json();

        if (isset($body['errors'])) {
            throw new RuntimeException(
                'Shopify Storefront error: '.json_encode($body['errors'])
            );
        }

        return $body['data'] ?? [];
    }

    private function request(): PendingRequest
    {
        $endpoint = "https://{$this->domain}/api/{$this->apiVersion}/graphql.json";

        return Http::asJson()
            ->acceptJson()
            ->withHeaders(['X-Shopify-Storefront-Access-Token' => $this->token])
            ->timeout(10)
            ->retry(2, 200)
            ->baseUrl($endpoint);
    }
}
