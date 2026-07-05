<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Domains\Shop\Contracts\ProductCatalog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.public-layout', ['title' => 'Shop'])]
class ProductPage extends Component
{
    public string $handle;

    public ?string $selectedVariantId = null;

    public ?string $notice = null;

    public function mount(ProductCatalog $catalog, string $handle): void
    {
        $product = $catalog->find($handle);
        abort_unless($product, 404);

        $this->handle = $handle;

        // Default to the first available variant (or the first, if all sold out).
        $variants = collect($product->variants);
        $this->selectedVariantId = $variants->firstWhere('available', true)?->id
            ?? $variants->first()?->id;
    }

    public function addToCart(): void
    {
        // Cart + Shopify hosted checkout is the next increment. See design §3.1.
        $this->notice = 'Adding to Cart is coming soon — checkout will hand off to Shopify.';
    }

    public function render(ProductCatalog $catalog)
    {
        $product = $catalog->find($this->handle);
        abort_unless($product, 404);

        $variants = collect($product->variants);

        return view('livewire.shop.product-page', [
            'product' => $product,
            'selected' => $variants->firstWhere('id', $this->selectedVariantId)
                ?? $variants->first(),
        ]);
    }
}
