<?php

declare(strict_types=1);

namespace App\Domains\Resources\Support;

use App\Domains\Resources\Models\Resource;

/**
 * Decides whether the current visitor may download a Resource.
 *
 * Free resources are always open. Email-gated resources open for authenticated
 * users (we already have their email) or for an anonymous visitor who has
 * unlocked this resource in the current session. Paid goods live in Shopify and
 * are never gated here. See docs/system-design.md §3.2.
 */
class ResourceGate
{
    public const SESSION_KEY = 'resource_unlocks';

    public static function unlocked(Resource $resource): bool
    {
        if (! $resource->isEmailGated()) {
            return true;
        }

        if (auth()->check() && auth()->user()?->hasVerifiedEmail()) {
            return true;
        }

        return in_array($resource->slug, session(self::SESSION_KEY, []), true);
    }
}
