<?php

namespace App\Domains\Game\Http\Controllers;

use App\Domains\Game\Support\Story;
use Illuminate\View\View;

class AdventureController
{
    /**
     * The Adventure game. Loads the story and hands it to the accessible
     * client-side engine (resources/js/adventure.js). Progress is kept in the
     * browser; account sync is a future increment. See docs/system-design.md §3.4.
     *
     * Served on the play subdomain when PLAY_DOMAIN is configured, otherwise at
     * /play on the primary host (local/CI). The engine is framework-agnostic, so
     * it can later move to a fully static play.neuroresource.org build — see
     * DEPLOYMENT.md and docs/adventure-authoring.md.
     */
    public function __invoke(): View
    {
        $story = Story::default();

        return view('adventure.play', [
            'storyJson' => $story->toJson(),
            'storyTitle' => $story->title(),
        ]);
    }
}
