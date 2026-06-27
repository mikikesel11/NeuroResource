<?php

namespace Tests\Feature\Shop;

use App\Livewire\Shop\ProductPage;
use Livewire\Livewire;
use Tests\TestCase;

class ShopPagesTest extends TestCase
{
    // No Shopify token in testing => the FakeCatalog fixtures back these pages.

    public function test_shop_lists_products_with_prices(): void
    {
        $response = $this->get(route('shop'));

        $response->assertOk();
        $response->assertSee('Weighted Lap Pad');
        $response->assertSee('$39.00', false);
        $response->assertSee('Sold Out'); // the unavailable fixture product
    }

    public function test_product_page_shows_details_and_variants(): void
    {
        $response = $this->get(route('shop.product', 'weighted-lap-pad'));

        $response->assertOk();
        $response->assertSee('Weighted Lap Pad');
        $response->assertSee('Choose an Option');
        $response->assertSee('2 kg');
        $response->assertSee('3 kg');
    }

    public function test_unknown_product_returns_404(): void
    {
        $this->get(route('shop.product', 'no-such-product'))->assertNotFound();
    }

    public function test_selecting_a_variant_updates_the_displayed_price(): void
    {
        Livewire::test(ProductPage::class, ['handle' => 'weighted-lap-pad'])
            ->assertSee('$39.00')
            ->set('selectedVariantId', 'fake/1/2')
            ->assertSee('$45.00');
    }

    public function test_add_to_cart_shows_coming_soon_notice(): void
    {
        Livewire::test(ProductPage::class, ['handle' => 'weighted-lap-pad'])
            ->assertSet('notice', null)
            ->call('addToCart')
            ->assertSee('coming soon');
    }
}
