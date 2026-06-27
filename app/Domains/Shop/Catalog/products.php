<?php

/*
| Fixture products for the FakeCatalog — used in development and CI when no
| Shopify store is connected. Shaped to mirror the fields ShopifyCatalog maps
| from the Storefront API. Replace with a real store by setting Shopify creds.
|
| Images are intentionally null so the catalog works offline; the views show a
| calm placeholder. Real product images come from Shopify.
*/

return [
    [
        'id' => 'fake/1',
        'handle' => 'weighted-lap-pad',
        'title' => 'Weighted Lap Pad',
        'description' => 'A gentle, even Weight for the lap — grounding pressure that supports Focus and Regulation while seated. Machine washable cover.',
        'price' => ['amount' => '39.00', 'currency' => 'USD'],
        'available' => true,
        'variants' => [
            ['id' => 'fake/1/1', 'title' => '2 kg', 'amount' => '39.00', 'available' => true],
            ['id' => 'fake/1/2', 'title' => '3 kg', 'amount' => '45.00', 'available' => true],
        ],
    ],
    [
        'id' => 'fake/2',
        'handle' => 'visual-timer',
        'title' => 'Visual Focus Timer',
        'description' => 'A silent, color-coded Timer that makes the passage of Time visible — supporting Time Awareness without the stress of ticking or alarms.',
        'price' => ['amount' => '24.50', 'currency' => 'USD'],
        'available' => true,
        'variants' => [
            ['id' => 'fake/2/1', 'title' => 'Standard', 'amount' => '24.50', 'available' => true],
        ],
    ],
    [
        'id' => 'fake/3',
        'handle' => 'noise-reducing-earplugs',
        'title' => 'Noise-Reducing Earplugs',
        'description' => 'Reusable Earplugs that lower overall Sensory Load while keeping speech clear — for loud rooms, travel, and busy days.',
        'price' => ['amount' => '29.00', 'currency' => 'USD'],
        'available' => true,
        'variants' => [
            ['id' => 'fake/3/1', 'title' => 'Standard fit', 'amount' => '29.00', 'available' => true],
            ['id' => 'fake/3/2', 'title' => 'Small fit', 'amount' => '29.00', 'available' => false],
        ],
    ],
    [
        'id' => 'fake/4',
        'handle' => 'fidget-set',
        'title' => 'Quiet Fidget Set',
        'description' => "A small Set of quiet, pocket-sized Fidgets for the hands — discreet Tools for Self-Regulation that won't disturb the people around you.",
        'price' => ['amount' => '18.00', 'currency' => 'USD'],
        'available' => true,
        'variants' => [
            ['id' => 'fake/4/1', 'title' => 'Set of 5', 'amount' => '18.00', 'available' => true],
        ],
    ],
    [
        'id' => 'fake/5',
        'handle' => 'visual-schedule-cards',
        'title' => 'Visual Schedule Cards',
        'description' => 'Reusable Cards for building Predictable, Visual routines — reducing the Executive Function cost of remembering what comes next.',
        'price' => ['amount' => '22.00', 'currency' => 'USD'],
        'available' => true,
        'variants' => [
            ['id' => 'fake/5/1', 'title' => 'Starter deck', 'amount' => '22.00', 'available' => true],
        ],
    ],
    [
        'id' => 'fake/6',
        'handle' => 'chewable-necklace',
        'title' => 'Chewable Necklace',
        'description' => 'A durable, food-grade Necklace for safe oral Sensory input — a calming outlet that travels with you.',
        'price' => ['amount' => '16.00', 'currency' => 'USD'],
        'available' => false,
        'variants' => [
            ['id' => 'fake/6/1', 'title' => 'Charcoal', 'amount' => '16.00', 'available' => false],
        ],
    ],
];
