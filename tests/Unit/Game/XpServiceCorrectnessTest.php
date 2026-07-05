<?php

declare(strict_types=1);

namespace Tests\Unit\Game;

use App\Domains\Game\Models\XpEvent;
use App\Domains\Game\Services\XpService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class XpServiceCorrectnessTest extends TestCase
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

    // --- H1: amount bounds ---------------------------------------------------

    public function test_award_throws_for_negative_amount(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->service->award($user, 'card_completed', -1);
    }

    public function test_award_throws_for_amount_above_column_max(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->service->award($user, 'card_completed', XpService::MAX_AWARD + 1);
    }

    public function test_award_does_not_write_when_amount_is_out_of_range(): void
    {
        $user = User::factory()->create();

        try {
            $this->service->award($user, 'card_completed', 70000);
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertDatabaseCount('xp_events', 0);
    }

    public function test_award_succeeds_at_lower_boundary(): void
    {
        $user = User::factory()->create();

        $event = $this->service->award($user, 'card_completed', XpService::MIN_AWARD);

        $this->assertNotNull($event);
        $this->assertSame(0, $event->amount);
    }

    public function test_award_succeeds_at_upper_boundary(): void
    {
        $user = User::factory()->create();

        $event = $this->service->award($user, 'card_completed', XpService::MAX_AWARD);

        $this->assertNotNull($event);
        $this->assertSame(65535, $event->amount);
    }

    // --- H2: boundary / period consistency -----------------------------------

    public function test_daily_total_excludes_previous_day_and_includes_today(): void
    {
        Carbon::setTestNow('2026-01-15 09:00:00');
        $user = User::factory()->create();

        $this->xpOn($user, '2026-01-14 23:59:59', 10); // yesterday — excluded
        $this->xpOn($user, '2026-01-15 00:00:01', 15); // today — included
        $this->xpOn($user, '2026-01-15 23:59:58', 5);  // today — included

        $this->assertSame(20, $this->service->dailyTotal($user));
    }

    public function test_daily_total_defaults_to_now(): void
    {
        Carbon::setTestNow('2026-03-10 12:00:00');
        $user = User::factory()->create();

        $this->xpOn($user, '2026-03-10 08:00:00', 42);

        $this->assertSame(42, $this->service->dailyTotal($user));
    }

    public function test_weekly_total_includes_events_at_week_edges_only(): void
    {
        Carbon::setTestNow('2026-01-14'); // Wednesday
        $user = User::factory()->create();

        // Carbon default week: Monday 2026-01-12 .. Sunday 2026-01-18.
        $this->xpOn($user, '2026-01-11 23:59:59', 20); // just before week start — excluded
        $this->xpOn($user, '2026-01-12 00:00:00', 30); // exactly week start — included
        $this->xpOn($user, '2026-01-18 23:59:59', 10); // exactly week end — included
        $this->xpOn($user, '2026-01-19 00:00:00', 99); // just after week end — excluded

        $this->assertSame(40, $this->service->weeklyTotal($user));
    }

    public function test_monthly_total_includes_month_edges_only(): void
    {
        Carbon::setTestNow('2026-02-15');
        $user = User::factory()->create();

        $this->xpOn($user, '2026-01-31 23:59:59', 7);  // prior month — excluded
        $this->xpOn($user, '2026-02-01 00:00:00', 11); // month start — included
        $this->xpOn($user, '2026-02-28 23:59:59', 13); // month end — included
        $this->xpOn($user, '2026-03-01 00:00:00', 17); // next month — excluded

        $this->assertSame(24, $this->service->monthlyTotal($user));
    }

    public function test_yearly_total_includes_year_edges_only(): void
    {
        Carbon::setTestNow('2026-06-15');
        $user = User::factory()->create();

        $this->xpOn($user, '2025-12-31 23:59:59', 3);  // prior year — excluded
        $this->xpOn($user, '2026-01-01 00:00:00', 5);  // year start — included
        $this->xpOn($user, '2026-12-31 23:59:59', 9);  // year end — included
        $this->xpOn($user, '2027-01-01 00:00:00', 21); // next year — excluded

        $this->assertSame(14, $this->service->yearlyTotal($user));
    }

    public function test_period_totals_are_consistent_for_a_known_event_set(): void
    {
        Carbon::setTestNow('2026-01-14 12:00:00'); // Wednesday
        $user = User::factory()->create();

        // All three events fall in the same day, week, month and year.
        $this->xpOn($user, '2026-01-12 10:00:00', 10); // Monday (week start)
        $this->xpOn($user, '2026-01-14 09:00:00', 20); // today
        $this->xpOn($user, '2026-01-14 15:00:00', 5);  // today

        // Daily: only the two "today" events.
        $this->assertSame(25, $this->service->dailyTotal($user));
        // Weekly/monthly/yearly: all three events.
        $this->assertSame(35, $this->service->weeklyTotal($user));
        $this->assertSame(35, $this->service->monthlyTotal($user));
        $this->assertSame(35, $this->service->yearlyTotal($user));
        $this->assertSame(35, $this->service->allTimeTotal($user));
    }
}
