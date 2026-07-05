<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    | The site ships English first. Add locales here as translations become
    | ready — routing and content fall back to the fallback locale until then.
    | See docs/system-design.md §7a.
    */

    'locales' => [
        'supported' => ['en'],          // add e.g. 'es', 'fr' when ready
        'fallback' => 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Accessibility Themes
    |--------------------------------------------------------------------------
    | The user-selectable themes wired to CSS custom properties in
    | resources/css/accessibility.css. "low-stimulation" is the calm,
    | muted default-friendly option for the NeuroDivergent audience.
    */

    'themes' => ['light', 'dark', 'high-contrast', 'low-stimulation'],

    'default_theme' => 'light',

    /*
    |--------------------------------------------------------------------------
    | Resource Access Tiers
    |--------------------------------------------------------------------------
    | "free" = open download. "email" = email-gated lead capture (self-hosted).
    | Anything paid lives in Shopify, never gated here. See design §3.2.
    */

    'resource_access' => ['free', 'email'],

    /*
    |--------------------------------------------------------------------------
    | Email-Gate Unlock Flow
    |--------------------------------------------------------------------------
    | unlock_link_ttl_hours — how long a confirmation link stays valid. Links
    | are signed + expiring so a forwarded/archived link can't unlock forever.
    | unlock_max_attempts / unlock_decay_seconds — server-side rate limit on the
    | public unlock endpoint (per IP + email) to prevent mail-bombing abuse.
    */

    'unlock_link_ttl_hours' => (int) env('RESOURCE_UNLOCK_LINK_TTL_HOURS', 24),

    'unlock_max_attempts' => (int) env('RESOURCE_UNLOCK_MAX_ATTEMPTS', 5),

    'unlock_decay_seconds' => (int) env('RESOURCE_UNLOCK_DECAY_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Domains
    |--------------------------------------------------------------------------
    | The Adventure game is intended to live on its own subdomain
    | (play.neuroresource.org). When PLAY_DOMAIN is set, the game + its progress
    | API are served on that host and the rest of the site on the primary host;
    | route() then generates the correct host for each. When PLAY_DOMAIN is
    | empty (local/dev/CI), everything runs on one host with the game at /play.
    |
    | For shared login across both hosts, set SESSION_DOMAIN to the registrable
    | domain with a leading dot (e.g. ".neuroresource.org"). See DEPLOYMENT.md.
    */

    /*
    |--------------------------------------------------------------------------
    | Card Decks
    |--------------------------------------------------------------------------
    | Visual configuration per deck slug. back_image is served from public/
    | (use asset()). bg/accent/border are Tailwind classes applied to the
    | card face. Add new decks here — no code change required.
    */

    'decks' => [
        'focus' => [
            'back_image' => 'images/cards/focus/back.jpg',
            'bg' => 'from-blue-50 to-blue-100',
            'accent' => 'text-blue-800',
            'border' => 'border-blue-200',
        ],
        'calm' => [
            'back_image' => 'images/cards/calm/back.jpg',
            'bg' => 'from-green-50 to-teal-100',
            'accent' => 'text-teal-800',
            'border' => 'border-teal-200',
        ],
        'brave' => [
            'back_image' => 'images/cards/brave/back.jpg',
            'bg' => 'from-amber-50 to-orange-100',
            'accent' => 'text-orange-800',
            'border' => 'border-orange-200',
        ],
    ],

    'domains' => [
        'primary' => env('APP_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        'play' => env('PLAY_DOMAIN'),
    ],

];
