<?php

namespace Tests\Unit\Game;

use App\Domains\Game\Models\Card;
use App\Domains\Game\Models\UserCardPull;
use App\Domains\Game\Services\CardService;
use App\Domains\Game\Services\XpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class CardServiceTest extends TestCase
{
    use RefreshDatabase;

    private CardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CardService(new XpService);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function card(string $deck, string $name = 'Card', int $xp = 10): Card
    {
        return Card::create([
            'name' => $name,
            'description' => 'A test card.',
            'deck' => $deck,
            'xp_earned' => $xp,
            'is_active' => true,
        ]);
    }

    public function test_draws_from_least_pulled_cards(): void
    {
        $user = User::factory()->create();
        $card1 = $this->card('focus', 'Breathe');
        $card2 = $this->card('focus', 'Pause');

        UserCardPull::create([
            'user_id' => $user->id,
            'card_id' => $card1->id,
            'pull_count' => 1,
            'last_pulled_at' => now(),
        ]);

        $result = $this->service->draw($user, 'focus');

        $this->assertSame($card2->id, $result['card']->id);
    }

    public function test_deck_must_complete_before_card_repeats(): void
    {
        $user = User::factory()->create();
        foreach (['Alpha', 'Beta', 'Gamma'] as $name) {
            $this->card('calm', $name);
        }

        $drawn = collect();
        for ($i = 0; $i < 3; $i++) {
            $drawn->push($this->service->draw($user, 'calm')['card']->id);
        }

        $this->assertSame(3, $drawn->unique()->count(), 'All three cards must appear before any repeats');

        $fourth = $this->service->draw($user, 'calm');
        $this->assertSame(2, $fourth['pull_count'], 'Fourth draw starts second cycle at pull_count 2');
    }

    public function test_draw_does_not_award_xp(): void
    {
        $user = User::factory()->create();
        $this->card('brave', 'Stand Tall', 15);

        $result = $this->service->draw($user, 'brave');

        $this->assertArrayNotHasKey('xp_awarded', $result);
        $this->assertDatabaseCount('xp_events', 0);
    }

    public function test_complete_awards_xp(): void
    {
        $user = User::factory()->create();
        $card = $this->card('brave', 'Stand Tall', 15);

        $event = $this->service->complete($user, $card);

        $this->assertSame(15, $event?->amount);
        $this->assertDatabaseHas('xp_events', [
            'user_id' => $user->id,
            'source' => 'card:brave-Stand Tall',
            'amount' => 15,
        ]);
    }

    public function test_throws_when_deck_has_no_active_cards(): void
    {
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);

        $this->service->draw($user, 'empty');
    }

    public function test_pull_count_increments_on_repeated_draws(): void
    {
        $user = User::factory()->create();
        $this->card('solo', 'Only Card');

        $first = $this->service->draw($user, 'solo');
        $second = $this->service->draw($user, 'solo');

        $this->assertSame(1, $first['pull_count']);
        $this->assertSame(2, $second['pull_count']);
    }

    public function test_inactive_cards_are_excluded_from_draw(): void
    {
        $user = User::factory()->create();
        $this->card('mixed', 'Active');
        Card::create([
            'name' => 'Inactive',
            'description' => 'Should not be drawn.',
            'deck' => 'mixed',
            'xp_earned' => 10,
            'is_active' => false,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->draw($user, 'mixed');
            $this->assertSame('Active', $result['card']->name);
        }
    }

    public function test_draw_with_preexisting_pull_row_increments_without_duplicating(): void
    {
        $user = User::factory()->create();
        $card = $this->card('solo', 'Only Card');
        UserCardPull::create([
            'user_id' => $user->id,
            'card_id' => $card->id,
            'pull_count' => 2,
            'last_pulled_at' => now()->subDay(),
        ]);

        $result = $this->service->draw($user, 'solo');

        $this->assertSame(3, $result['pull_count'], 'Existing row increments rather than resetting');
        $this->assertSame(
            1,
            UserCardPull::where('user_id', $user->id)->where('card_id', $card->id)->count(),
            'Drawing an existing card must not create a duplicate pull row'
        );
    }

    public function test_draw_updates_last_pulled_at_timestamp(): void
    {
        Carbon::setTestNow('2026-07-04 12:00:00');

        $user = User::factory()->create();
        $card = $this->card('solo', 'Only Card');

        $this->service->draw($user, 'solo');

        $pull = UserCardPull::where('user_id', $user->id)
            ->where('card_id', $card->id)
            ->first();

        $this->assertNotNull($pull);
        $this->assertSame(1, $pull->pull_count);
        $this->assertTrue(Carbon::parse('2026-07-04 12:00:00')->equalTo($pull->last_pulled_at));
    }

    public function test_pull_counts_for_user_returns_card_id_keyed_map(): void
    {
        $user = User::factory()->create();
        $card = $this->card('focus', 'Map Card');
        UserCardPull::create([
            'user_id' => $user->id,
            'card_id' => $card->id,
            'pull_count' => 3,
            'last_pulled_at' => now(),
        ]);

        $counts = $this->service->pullCountsForUser($user, 'focus');

        $this->assertSame(3, (int) $counts[$card->id]);
    }
}
