<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Headless Shopify (Storefront API). Catalog + cart render on our site;
    // checkout hands off to Shopify. See docs/system-design.md §3.1.
    'shopify' => [
        'storefront_domain' => env('SHOPIFY_STOREFRONT_DOMAIN'), // e.g. neuroscouts.myshopify.com
        'storefront_token' => env('SHOPIFY_STOREFRONT_TOKEN'),   // public Storefront access token
        'api_version' => env('SHOPIFY_API_VERSION', '2025-07'),
        'cache_ttl' => (int) env('SHOPIFY_CACHE_TTL', 600),
        'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),       // HMAC verification
    ],

    // Plausible — cookieless analytics, no consent banner. See §11.
    'plausible' => [
        'domain' => env('PLAUSIBLE_DOMAIN'),                     // e.g. neuroscouts.org
        'src' => env('PLAUSIBLE_SRC', 'https://plausible.io/js/script.js'),
    ],

];
