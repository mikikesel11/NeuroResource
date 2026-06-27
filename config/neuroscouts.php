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

];
