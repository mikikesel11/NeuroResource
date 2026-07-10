<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Domains\Game\Models\Card;
use App\Livewire\Game\CardDraw;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CardDrawTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['neuroresource.decks.focus' => [
            'title' => 'Focus',
            'prompt' => 'Help getting started',
            'access' => 'free',
            'back_image' => 'images/cards/focus/back.jpg',
            'bg' => 'from-blue-50 to-blue-100',
            'accent' => 'text-blue-800',
            'border' => 'border-blue-200',
        ]]);
    }

    private function card(string $deck, string $name = 'Test Card', array $subtasks = [], ?int $timerMinutes = null): Card
    {
        return Card::create([
            'name' => $name,
            'description' => 'A test card.',
            'deck' => $deck,
            'xp_earned' => 10,
            'is_active' => true,
            'subtasks' => $subtasks ?: null,
            'timer_minutes' => $timerMinutes,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cards.draw', ['deck' => 'focus']))
            ->assertRedirect(route('login'));
    }

    public function test_renders_in_idle_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->assertSet('phase', 'idle')
            ->assertSet('result', null)
            ->assertSet('checkedSubtasks', []);
    }

    public function test_draw_transitions_to_revealed_with_card_data(): void
    {
        $user = User::factory()->create();
        $card = $this->card('focus', 'Deep Breath');

        $component = Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw');

        $component->assertSet('phase', 'revealed');
        $this->assertNotNull($component->get('result'));
        $this->assertSame($card->id, $component->get('result')['card']->id);
    }

    public function test_draw_dispatches_card_drawn_browser_event(): void
    {
        $user = User::factory()->create();
        $this->card('focus');

        Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw')
            ->assertDispatched('card-drawn');
    }

    public function test_toggle_subtask_checks_and_unchecks_index(): void
    {
        $user = User::factory()->create();
        $this->card('focus');

        $component = Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw')
            ->call('toggleSubtask', 0);

        $this->assertContains(0, $component->get('checkedSubtasks'));

        $component->call('toggleSubtask', 0);

        $this->assertNotContains(0, $component->get('checkedSubtasks'));
    }

    public function test_reset_draw_clears_result_and_returns_to_idle(): void
    {
        $user = User::factory()->create();
        $this->card('focus');

        Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw')
            ->call('resetDraw')
            ->assertSet('phase', 'idle')
            ->assertSet('result', null)
            ->assertSet('checkedSubtasks', []);
    }

    public function test_unknown_deck_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cards.draw', ['deck' => 'nonexistent']))
            ->assertNotFound();
    }

    public function test_draw_does_not_award_xp(): void
    {
        $user = User::factory()->create();
        $this->card('focus');

        Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw')
            ->assertSet('cardDone', false);

        $this->assertDatabaseCount('xp_events', 0);
    }

    public function test_mark_done_sets_card_done_and_awards_xp_once(): void
    {
        $user = User::factory()->create();
        $this->card('focus');

        $component = Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw')
            ->call('markDone')
            ->assertSet('cardDone', true);

        $this->assertSame(10, $component->get('result')['xp_awarded']);
        $this->assertDatabaseCount('xp_events', 1);
    }

    public function test_mark_done_twice_awards_xp_only_once(): void
    {
        $user = User::factory()->create();
        $this->card('focus');

        Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw')
            ->call('markDone')
            ->call('markDone');

        $this->assertDatabaseCount('xp_events', 1);
    }

    public function test_checking_last_subtask_completes_card_and_awards_once(): void
    {
        $user = User::factory()->create();
        $this->card('focus', 'Two Steps', ['First', 'Second']);

        $component = Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw')
            ->call('toggleSubtask', 0)
            ->assertSet('cardDone', false)
            ->call('toggleSubtask', 1)
            ->assertSet('cardDone', true);

        $this->assertDatabaseCount('xp_events', 1);
        $this->assertSame(10, $component->get('result')['xp_awarded']);
    }

    public function test_non_final_subtask_does_not_complete_card(): void
    {
        $user = User::factory()->create();
        $this->card('focus', 'Two Steps', ['First', 'Second']);

        Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw')
            ->call('toggleSubtask', 0)
            ->assertSet('cardDone', false);

        $this->assertDatabaseCount('xp_events', 0);
    }

    public function test_reset_draw_clears_card_done(): void
    {
        $user = User::factory()->create();
        $this->card('focus');

        Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw')
            ->call('markDone')
            ->assertSet('cardDone', true)
            ->call('resetDraw')
            ->assertSet('cardDone', false);
    }

    public function test_timer_minutes_is_exposed_on_result(): void
    {
        $user = User::factory()->create();
        $this->card('focus', 'Timed', [], 20);

        $component = Livewire::actingAs($user)
            ->test(CardDraw::class, ['deck' => 'focus'])
            ->call('draw');

        $this->assertSame(20, $component->get('result')['card']->timer_minutes);
    }
}
