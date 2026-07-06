<?php

declare(strict_types=1);

namespace App\Domains\Game\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Cross-device Adventure save for authenticated players. The engine
 * (resources/js/adventure.js) syncs here in addition to localStorage, so a
 * logged-in player resumes on any device. See docs/system-design.md §3.4.
 */
class GameProgressController
{
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'story_id' => ['required', 'string', 'max:255'],
        ]);

        $progress = $request->user()
            ->gameProgress()
            ->where('story_id', $data['story_id'])
            ->first();

        if (! $progress) {
            return response()->json(['scene' => null]);
        }

        return response()->json([
            'scene' => $progress->scene_id,
            'history' => $progress->state_json['history'] ?? [],
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'story_id' => ['required', 'string', 'max:255'],
            'scene_id' => ['required', 'string', 'max:255'],
            'state' => ['array'],
            'state.history' => ['array'],
            'state.history.*' => ['string', 'max:255'],
        ]);

        $request->user()->gameProgress()->updateOrCreate(
            ['story_id' => $data['story_id']],
            [
                'scene_id' => $data['scene_id'],
                'state_json' => ['history' => $data['state']['history'] ?? []],
            ],
        );

        return response()->noContent();
    }
}
