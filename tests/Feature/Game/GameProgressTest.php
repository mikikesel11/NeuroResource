<?php

namespace Tests\Feature\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_or_write_progress(): void
    {
        $this->get(route('play.progress.show', ['story_id' => 'The Quiet Path']))
            ->assertRedirect(route('login'));

        $this->post(route('play.progress.store'), [
            'story_id' => 'The Quiet Path',
            'scene_id' => 'clearing',
        ])->assertRedirect(route('login'));
    }

    public function test_authenticated_player_saves_progress(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('play.progress.store'), [
                'story_id' => 'The Quiet Path',
                'scene_id' => 'stream',
                'state' => ['history' => ['clearing']],
            ])
            ->assertNoContent();

        $progress = $user->gameProgress()->first();
        $this->assertSame('The Quiet Path', $progress->story_id);
        $this->assertSame('stream', $progress->scene_id);
        $this->assertSame(['clearing'], $progress->state_json['history']);
    }

    public function test_saving_again_updates_the_same_record(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('play.progress.store'), [
            'story_id' => 'The Quiet Path', 'scene_id' => 'stream', 'state' => ['history' => ['clearing']],
        ])->assertNoContent();

        $this->actingAs($user)->post(route('play.progress.store'), [
            'story_id' => 'The Quiet Path', 'scene_id' => 'rest', 'state' => ['history' => ['clearing', 'stream']],
        ])->assertNoContent();

        $this->assertDatabaseCount('game_progress', 1);
        $this->assertSame('rest', $user->gameProgress()->first()->scene_id);
    }

    public function test_show_returns_empty_scene_when_nothing_saved(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('play.progress.show', ['story_id' => 'Unstarted']))
            ->assertOk()
            ->assertExactJson(['scene' => null]);
    }

    public function test_show_returns_saved_scene_and_history(): void
    {
        $user = User::factory()->create();

        $user->gameProgress()->create([
            'story_id' => 'The Quiet Path',
            'scene_id' => 'meadow',
            'state_json' => ['history' => ['clearing']],
        ]);

        $this->actingAs($user)
            ->getJson(route('play.progress.show', ['story_id' => 'The Quiet Path']))
            ->assertOk()
            ->assertExactJson(['scene' => 'meadow', 'history' => ['clearing']]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('play.progress.store'), ['story_id' => 'The Quiet Path'])
            ->assertStatus(422);
    }
}
