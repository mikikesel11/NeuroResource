<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Livewire\Game\CardQuestionnaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CardQuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['neuroresource.decks' => [
            'focus' => [
                'title' => 'Focus',
                'prompt' => 'Help getting started',
                'access' => 'free',
                'back_image' => 'images/cards/focus/back.jpg',
                'bg' => 'from-blue-50 to-blue-100',
                'accent' => 'text-blue-800',
                'border' => 'border-blue-200',
            ],
            'calm' => [
                'title' => 'Calm',
                'prompt' => 'A moment to settle',
                'access' => 'free',
                'back_image' => 'images/cards/calm/back.jpg',
                'bg' => 'from-green-50 to-teal-100',
                'accent' => 'text-teal-800',
                'border' => 'border-teal-200',
            ],
            'paid' => [
                'title' => 'Premium',
                'prompt' => 'Exclusive content',
                'access' => 'paid',
                'back_image' => null,
                'bg' => 'from-purple-50 to-purple-100',
                'accent' => 'text-purple-800',
                'border' => 'border-purple-200',
            ],
        ]]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cards.questionnaire'))
            ->assertRedirect(route('login'));
    }

    public function test_renders_entitled_deck_prompts(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CardQuestionnaire::class)
            ->assertSee('Help getting started')
            ->assertSee('A moment to settle');
    }

    public function test_choosing_a_deck_redirects_to_draw(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CardQuestionnaire::class)
            ->call('choose', 'calm')
            ->assertRedirect(route('cards.draw', ['deck' => 'calm']));
    }

    public function test_choosing_unknown_deck_returns_404(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CardQuestionnaire::class)
            ->call('choose', 'nonsense')
            ->assertStatus(404);
    }

    public function test_paid_deck_prompt_is_not_shown(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CardQuestionnaire::class)
            ->assertDontSee('Exclusive content');
    }

    public function test_paid_deck_cannot_be_chosen(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CardQuestionnaire::class)
            ->call('choose', 'paid')
            ->assertStatus(404);
    }
}
