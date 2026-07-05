<?php

namespace Tests\Unit\Game;

use App\Domains\Game\Models\Card;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardTest extends TestCase
{
    use RefreshDatabase;

    private function card(string $deck, bool $active = true): Card
    {
        return Card::create([
            'name' => 'Test Card',
            'description' => 'A card.',
            'deck' => $deck,
            'xp_earned' => 10,
            'is_active' => $active,
        ]);
    }

    public function test_deck_scope_filters_by_deck(): void
    {
        $this->card('focus');
        $this->card('calm');

        $this->assertSame(1, Card::deck('focus')->count());
        $this->assertSame(1, Card::deck('calm')->count());
    }

    public function test_active_scope_excludes_inactive_cards(): void
    {
        $this->card('focus', true);
        $this->card('focus', false);

        $this->assertSame(1, Card::active()->count());
    }

    public function test_xp_earned_is_cast_to_integer(): void
    {
        $card = $this->card('focus');

        $this->assertIsInt($card->xp_earned);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $card = $this->card('focus', true);

        $this->assertIsBool($card->is_active);
        $this->assertTrue($card->is_active);
    }
}
