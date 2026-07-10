<?php

declare(strict_types=1);

namespace App\Domains\Game\Support;

use App\Models\User;

class DeckAccess
{
    public static function allows(?User $user, string $deck): bool
    {
        if ($user === null) {
            return false;
        }

        $config = config("neuroresource.decks.{$deck}");

        if (! is_array($config)) {
            return false;
        }

        // TODO: paid-tier check goes here
        return ($config['access'] ?? null) === 'free';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function accessibleTo(?User $user): array
    {
        /** @var array<string, array<string, mixed>> $decks */
        $decks = config('neuroresource.decks', []);

        return array_filter($decks, fn (string $slug) => static::allows($user, $slug), ARRAY_FILTER_USE_KEY);
    }
}
