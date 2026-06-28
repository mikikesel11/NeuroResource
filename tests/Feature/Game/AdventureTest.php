<?php

namespace Tests\Feature\Game;

use App\Domains\Game\Support\Story;
use Tests\TestCase;

class AdventureTest extends TestCase
{
    public function test_play_page_renders_with_the_story(): void
    {
        $response = $this->get(route('play'));

        $response->assertOk();
        $response->assertSee('The Quiet Path');          // story title
        $response->assertSee('id="adventure"', false);   // engine mount point
        $response->assertSee('id="adventure-data"', false); // story JSON island
        $response->assertSee('A Clearing in the Woods');  // embedded scene content
    }

    public function test_shipped_story_is_valid(): void
    {
        // Content-integrity guard: any broken story.json fails CI here.
        $problems = Story::default()->validate();

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    public function test_validator_flags_a_choice_pointing_to_a_missing_scene(): void
    {
        $story = new Story([
            'title' => 'Broken',
            'start' => 'a',
            'scenes' => [
                'a' => ['title' => 'A', 'choices' => [['label' => 'Go', 'to' => 'ghost']]],
            ],
        ]);

        $problems = $story->validate();

        $this->assertNotEmpty($problems);
        $this->assertStringContainsString('ghost', implode(' ', $problems));
    }

    public function test_validator_flags_a_missing_start_scene(): void
    {
        $story = new Story([
            'title' => 'Broken',
            'start' => 'nowhere',
            'scenes' => [
                'a' => ['title' => 'A', 'ending' => true],
            ],
        ]);

        $this->assertNotEmpty($story->validate());
    }

    public function test_validator_passes_a_well_formed_story(): void
    {
        $story = new Story([
            'title' => 'Good',
            'start' => 'a',
            'scenes' => [
                'a' => ['title' => 'A', 'text' => ['Hi'], 'choices' => [['label' => 'On', 'to' => 'b']]],
                'b' => ['title' => 'B', 'text' => ['End'], 'ending' => true],
            ],
        ]);

        $this->assertSame([], $story->validate());
    }
}
