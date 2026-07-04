<?php

use App\Domains\Content\Models\Post;

return [
    'feeds' => [
        'blog' => [
            // Class + method returning the feed items. See Post::getFeedItems().
            'items' => [Post::class, 'getFeedItems'],

            // The feed is served at this URL (route auto-registered by the package).
            'url' => '/blog/feed',

            'title' => 'The NeuroResource Blog',
            'description' => 'Plain-language writing on Focus, Regulation, and life as a NeuroDivergent person.',
            'language' => 'en-US',

            // No feed image for now.
            'image' => '',

            // 'rss', 'atom', or 'json'.
            'format' => 'rss',

            'view' => 'feed::rss',

            // Auto-detect mime/content types.
            'type' => '',
            'contentType' => '',
        ],
    ],
];
