<?php

declare(strict_types=1);

namespace App\Domains\Game\Listeners;

use App\Domains\Game\Services\XpService;
use Illuminate\Auth\Events\Login;

class AwardDailyLoginXp
{
    public function handle(Login $event): void
    {
        app(XpService::class)->award($event->user, 'daily_login', 10);
    }
}
