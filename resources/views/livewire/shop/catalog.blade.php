<div class="mx-auto max-w-5xl px-4 py-16">
    <header class="mb-8">
        <p class="text-sm uppercase tracking-wide text-[var(--ns-muted)]">The Shop</p>
        <h1 class="mt-1 text-3xl font-semibold">Tools for Focus, Regulation, and Rest</h1>
        <p class="mt-3 max-w-2xl text-[var(--ns-muted)]">
            A small, considered collection. Every item is chosen to lower Sensory Load
            and support the way You work. Checkout is handled securely by Shopify.
        </p>
    </header>

    @if (empty($products))
        <p class="text-[var(--ns-muted)]">No Products are available right now. Please check back soon.</p>
    @else
        <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $product)
                <li>
                    <a href="{{ route('shop.product', $product->handle) }}" wire:navigate
                       class="group block h-full overflow-hidden rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] focus-visible:border-[var(--ns-accent)] hover:border-[var(--ns-accent)]">
                        <x-shop.product-image :product="$product" class="aspect-square w-full" />

                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="font-semibold group-hover:text-[var(--ns-accent)]">{{ $product->title }}</h2>
                                @unless ($product->available)
                                    <span class="shrink-0 rounded bg-[var(--ns-border)] px-2 py-0.5 text-xs">Sold Out</span>
                                @endunless
                            </div>
                            <p class="mt-1 text-[var(--ns-muted)]">
                                @if (count($product->variants) > 1)
                                    From {{ $product->price->formatted() }}
                                @else
                                    {{ $product->price->formatted() }}
                                @endif
                            </p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
