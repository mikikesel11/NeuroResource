<div class="mx-auto max-w-4xl px-4 py-12">
    <p class="mb-6">
        <a href="{{ route('shop') }}" wire:navigate
           class="text-sm text-[var(--ns-accent)] underline underline-offset-4">← Back to the Shop</a>
    </p>

    <div class="grid gap-8 md:grid-cols-2">
        <x-shop.product-image :product="$product"
            class="aspect-square w-full rounded-lg border border-[var(--ns-border)]" />

        <div>
            <h1 class="text-3xl font-semibold">{{ $product->title }}</h1>

            <p class="mt-2 text-xl text-[var(--ns-accent)]" aria-live="polite">
                {{ ($selected?->price ?? $product->price)->formatted() }}
            </p>

            @if ($product->description)
                <p class="mt-4 text-[var(--ns-muted)]">{{ $product->description }}</p>
            @endif

            @if (count($product->variants) > 1)
                <fieldset class="mt-6">
                    <legend class="mb-2 text-sm font-medium">Choose an Option</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->variants as $variant)
                            <label class="cursor-pointer rounded border px-3 py-1.5 text-sm
                                {{ $selected?->id === $variant->id ? 'border-[var(--ns-accent)] bg-[var(--ns-accent)] text-[var(--ns-accent-contrast)]' : 'border-[var(--ns-border)]' }}
                                {{ $variant->available ? '' : 'opacity-50' }}">
                                <input type="radio" class="sr-only" wire:model.live="selectedVariantId"
                                       value="{{ $variant->id }}" @disabled(! $variant->available)>
                                {{ $variant->title }}
                                @unless ($variant->available)<span class="sr-only"> (sold out)</span>@endunless
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <div class="mt-8">
                @if ($product->available && ($selected?->available ?? false))
                    <button type="button" wire:click="addToCart"
                            class="rounded-md bg-[var(--ns-accent)] px-6 py-3 font-medium text-[var(--ns-accent-contrast)]">
                        Add to Cart
                    </button>
                @else
                    <p class="rounded-md border border-[var(--ns-border)] px-6 py-3 text-[var(--ns-muted)]">
                        This Option is currently Sold Out.
                    </p>
                @endif
            </div>

            @if ($notice)
                <p role="status" class="mt-4 rounded-md bg-[var(--ns-surface)] px-4 py-3 text-sm">
                    {{ $notice }}
                </p>
            @endif
        </div>
    </div>
</div>
