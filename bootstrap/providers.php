<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\ShopServiceProvider;
use App\Providers\VoltServiceProvider;

return [
    AppServiceProvider::class,
    ShopServiceProvider::class,
    VoltServiceProvider::class,
];
