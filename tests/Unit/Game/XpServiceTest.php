<?php

declare(strict_types=1);

namespace Tests\Unit\Game;

use App\Domains\Game\Models\DailyLoginBonus;
use App\Domains\Game\Models\XpEvent;
use App\Domains\Game\Services\XpService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpServiceTest extends TestCase
{
    use RefreshDatabase;

    private XpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new XpService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function xpOn(User $user, string $date, int $amount, string $source = 'card_completed'): void
    {
        XpEvent::create([
            'user_id' => $user->id,
            'source' => $source,
            'amount' => $amount,
            'awarded_at' => Carbon::parse($date),
        ]);
    }

    public function test_daily_total_returns_zero_with_no_events(): void
    {
        $user = User::factory()->create();

        $this->assertSame(0, $this->service->dailyTotal($user));
    }

    public function test_daily_total_sums_only_todays_events(): void
    {
        Carbon::setTestNow('2026-01-15');
        $user = User::factory()->create();

        $this->xpOn($user, '2026-01-14', 10); // yesterday — excluded
        $this->xpOn($user, '2026-01-15', 15); // today

        $this->assertSame(15, $this->service->dailyTotal($user));
    }

    public function test_streak_of_three_consecutive_days(): void
    {
        Carbon::setTestNow('2026-01-15');
        $user = User::factory()->create();

        $this->xpOn($user, '2026-01-13', 10);
        $this->xpOn($user, '2026-01-14', 10);
        $this->xpOn($user, '2026-01-15', 10);

        $this->assertSame(3, $this->service->currentStreak($user));
    }

    public function test_streak_resets_after_gap(): void
    {
        Carbon::setTestNow('2026-01-15');
        $user = User::factory()->create();

        $this->xpOn($user, '2026-01-13', 10); // gap on 14th
        $this->xpOn($user, '2026-01-15', 10);

        $this->assertSame(1, $this->service->currentStreak($user));
    }

    public function test_streak_is_zero_when_last_event_is_older_than_yesterday(): void
    {
        Carbon::setTestNow('2026-01-15');
        $user = User::factory()->create();

        $this->xpOn($user, '2026-01-12', 10); // 3 days ago

        $this->assertSame(0, $this->service->currentStreak($user));
    }

    public function test_max_streak_picks_the_longer_historical_run(): void
    {
        Carbon::setTestNow('2026-01-20');
        $user = User::factory()->create();

        foreach (['2026-01-06', '2026-01-07', '2026-01-08', '2026-01-09', '2026-01-10'] as $date) {
            $this->xpOn($user, $date, 10);
        }

        $this->xpOn($user, '2026-01-19', 10);
        $this->xpOn($user, '2026-01-20', 10);

        $this->assertSame(5, $this->service->maxStreak($user));
    }

    public function test_weekly_total_excludes_prior_weeks(): void
    {
        Carbon::setTestNow('2026-01-14'); // Wednesday
        $user = User::factory()->create();

        $this->xpOn($user, '2026-01-05', 20); // prior week
        $this->xpOn($user, '2026-01-12', 30); // this week (Mon)
        $this->xpOn($user, '2026-01-14', 10); // this week (Wed)

        $this->assertSame(40, $this->service->weeklyTotal($user));
    }

    public function test_daily_stats_min_max_avg(): void
    {
        $user = User::factory()->create();

        $this->xpOn($user, '2026-01-01', 10);
        $this->xpOn($user, '2026-01-02', 20);
        $this->xpOn($user, '2026-01-03', 30);

        $stats = $this->service->dailyStats($user);

        $this->assertSame(10, $stats['min']);
        $this->assertSame(30, $stats['max']);
        $this->assertSame(20.0, $stats['avg']);
    }

    public function test_award_returns_null_on_duplicate_daily_login(): void
    {
        Carbon::setTestNow('2026-01-15');
        $user = User::factory()->create();

        $first = $this->service->award($user, 'daily_login', 10);
        $second = $this->service->award($user, 'daily_login', 10);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertDatabaseCount('xp_events', 1);
    }

    public function test_award_returns_null_when_daily_bonus_already_reserved_concurrently(): void
    {
        // Simulates a concurrent request winning the race: it has already
        // reserved today's slot via the unique constraint before this call
        // runs, so the DB-level guard — not a check-then-insert — must be
        // what stops the duplicate.
        Carbon::setTestNow('2026-01-15');
        $user = User::factory()->create();

        DailyLoginBonus::create([
            'user_id' => $user->id,
            'awarded_date' => Carbon::now()->toDateString(),
        ]);

        $result = $this->service->award($user, 'daily_login', 10);

        $this->assertNull($result);
        $this->assertDatabaseCount('xp_events', 0);
        $this->assertDatabaseCount('daily_login_bonuses', 1);
    }

    public function test_award_truncates_oversized_source_instead_of_failing(): void
    {
        $user = User::factory()->create();
        $overlongSource = 'card:'.str_repeat('x', 200);

        $event = $this->service->award($user, $overlongSource, 5);

        $this->assertNotNull($event);
        $this->assertLessThanOrEqual(160, mb_strlen($event->source));
        $this->assertSame(mb_substr($overlongSource, 0, 160), $event->source);
    }

    public function test_level_increases_every_100_xp(): void
    {
        $user = User::factory()->create();

        XpEvent::create([
            'user_id' => $user->id,
            'source' => 'card_completed',
            'amount' => 250,
            'awarded_at' => now(),
        ]);

        $this->assertSame(3, $this->service->level($user));
    }
}
