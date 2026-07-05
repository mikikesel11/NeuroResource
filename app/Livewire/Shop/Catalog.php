<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Domains\Shop\Contracts\ProductCatalog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.public-layout', ['title' => 'Shop'])]
class Catalog extends Component
{
    public function render(ProductCatalog $catalog)
    {
        return view('livewire.shop.catalog', [
            'products' => $catalog->products(),
        ]);
    }
}
