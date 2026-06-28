<?php

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
    | Domains
    |--------------------------------------------------------------------------
    | The Adventure game is intended to live on its own subdomain
    | (play.neuroscouts.org). When PLAY_DOMAIN is set, the game + its progress
    | API are served on that host and the rest of the site on the primary host;
    | route() then generates the correct host for each. When PLAY_DOMAIN is
    | empty (local/dev/CI), everything runs on one host with the game at /play.
    |
    | For shared login across both hosts, set SESSION_DOMAIN to the registrable
    | domain with a leading dot (e.g. ".neuroscouts.org"). See DEPLOYMENT.md.
    */

    'domains' => [
        'primary' => env('APP_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        'play' => env('PLAY_DOMAIN'),
    ],

];
