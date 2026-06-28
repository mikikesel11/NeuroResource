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
     * NOTE: served at /play within the app for now; it can later move to the
     * play.neuroscouts.org subdomain as a static build (the engine is
     * framework-agnostic). See docs/adventure-authoring.md.
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
