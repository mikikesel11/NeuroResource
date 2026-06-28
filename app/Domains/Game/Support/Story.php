<?php

namespace App\Domains\Game\Support;

use RuntimeException;

/**
 * Loads and validates an Adventure story (a JSON scene graph).
 *
 * The story format is intentionally simple so a content writer can author it
 * directly, or export from Twine and convert to this shape:
 *
 *   {
 *     "title": "...", "start": "<sceneId>",
 *     "scenes": {
 *       "<sceneId>": {
 *         "title": "...",
 *         "text": ["paragraph", "..."],
 *         "choices": [ { "label": "...", "to": "<sceneId>" } ],
 *         "ending": true            // optional; a scene with no choices
 *       }
 *     }
 *   }
 *
 * validate() returns a list of problems (empty = valid) and is exercised by a
 * test, so any content change is checked in CI. See docs/adventure-authoring.md.
 */
class Story
{
    /** @param array<string,mixed> $data */
    public function __construct(public readonly array $data) {}

    public static function fromFile(string $path): self
    {
        if (! is_file($path)) {
            throw new RuntimeException("Story file not found: $path");
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Story file is not valid JSON: $path");
        }

        return new self($decoded);
    }

    /** The default story shipped with the app. */
    public static function default(): self
    {
        return self::fromFile(resource_path('adventure/story.json'));
    }

    public function title(): string
    {
        return $this->data['title'] ?? 'The Adventure';
    }

    public function toJson(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Validate the scene graph. Returns a list of human-readable problems;
     * an empty list means the story is valid.
     *
     * @return list<string>
     */
    public function validate(): array
    {
        $problems = [];
        $scenes = $this->data['scenes'] ?? null;
        $start = $this->data['start'] ?? null;

        if (! is_array($scenes) || $scenes === []) {
            return ['Story has no scenes.'];
        }

        if (! $start) {
            $problems[] = 'Story is missing a "start" scene id.';
        } elseif (! isset($scenes[$start])) {
            $problems[] = "Start scene \"$start\" does not exist.";
        }

        foreach ($scenes as $id => $scene) {
            if (! isset($scene['title']) || $scene['title'] === '') {
                $problems[] = "Scene \"$id\" is missing a title.";
            }

            $choices = $scene['choices'] ?? [];
            $isEnding = ($scene['ending'] ?? false) === true;

            if (! $isEnding && $choices === []) {
                $problems[] = "Scene \"$id\" has no choices and is not marked as an ending.";
            }

            foreach ($choices as $i => $choice) {
                if (! isset($choice['label']) || $choice['label'] === '') {
                    $problems[] = "Scene \"$id\" choice #$i is missing a label.";
                }

                $to = $choice['to'] ?? null;
                if (! $to) {
                    $problems[] = "Scene \"$id\" choice #$i is missing a target (\"to\").";
                } elseif (! isset($scenes[$to])) {
                    $problems[] = "Scene \"$id\" choice #$i points to \"$to\", which does not exist.";
                }
            }
        }

        return $problems;
    }
}
