<?php

namespace Tests\Feature\Game;

use App\Domains\Game\Models\XpEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyLoginXpTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_first_login_of_the_day_awards_10_xp(): void
    {
        $user = User::factory()->create();

        event(new Login('web', $user, false));

        $this->assertDatabaseCount('xp_events', 1);
        $this->assertDatabaseHas('xp_events', [
            'user_id' => $user->id,
            'source'  => 'daily_login',
            'amount'  => 10,
        ]);
    }

    public function test_second_login_same_day_does_not_award_duplicate(): void
    {
        $user = User::factory()->create();

        event(new Login('web', $user, false));
        event(new Login('web', $user, false));

        $this->assertDatabaseCount('xp_events', 1);
    }

    public function test_login_next_day_awards_again(): void
    {
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::parse('2026-01-01 08:00:00'));
        event(new Login('web', $user, false));

        Carbon::setTestNow(Carbon::parse('2026-01-02 08:00:00'));
        event(new Login('web', $user, false));

        $this->assertDatabaseCount('xp_events', 2);
    }

    public function test_xp_metrics_endpoint_requires_auth(): void
    {
        $this->get(route('play.xp'))->assertRedirect(route('login'));
    }

    public function test_xp_metrics_endpoint_returns_expected_shape(): void
    {
        $user = User::factory()->create();
        event(new Login('web', $user, false));

        $this->actingAs($user)
            ->getJson(route('play.xp'))
            ->assertOk()
            ->assertJsonStructure(['daily', 'streak', 'max_streak', 'all_time', 'level']);
    }

    public function test_daily_total_reflects_xp_events(): void
    {
        $user = User::factory()->create();

        XpEvent::create(['user_id' => $user->id, 'source' => 'daily_login',   'amount' => 10, 'awarded_at' => Carbon::now()]);
        XpEvent::create(['user_id' => $user->id, 'source' => 'card_completed', 'amount' => 5,  'awarded_at' => Carbon::now()]);

        $this->actingAs($user)
            ->getJson(route('play.xp'))
            ->assertOk()
            ->assertJsonPath('daily', 15);
    }
}
