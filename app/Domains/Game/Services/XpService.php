<?php

declare(strict_types=1);

namespace App\Domains\Game\Services;

use App\Domains\Game\Models\XpEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class XpService
{
    /**
     * Inclusive bounds for a single XP award. The `amount` column is an
     * unsignedSmallInteger, so values outside this range cannot be stored.
     */
    public const MIN_AWARD = 0;

    public const MAX_AWARD = 65535;

    public function award(User $user, string $source, int $amount, ?Carbon $awardedAt = null): ?XpEvent
    {
        if ($amount < self::MIN_AWARD || $amount > self::MAX_AWARD) {
            throw new \InvalidArgumentException(sprintf(
                'XP award amount %d is out of range; must be between %d and %d.',
                $amount,
                self::MIN_AWARD,
                self::MAX_AWARD,
            ));
        }

        $awardedAt ??= Carbon::now();

        if ($source === 'daily_login' && $this->hasDailyBonus($user, $awardedAt)) {
            return null;
        }

        return XpEvent::create([
            'user_id' => $user->id,
            'source' => $source,
            'amount' => $amount,
            'awarded_at' => $awardedAt,
        ]);
    }

    public function hasDailyBonus(User $user, Carbon $date): bool
    {
        return XpEvent::where('user_id', $user->id)
            ->where('source', 'daily_login')
            ->whereDate('awarded_at', $date->toDateString())
            ->exists();
    }

    public function dailyTotal(User $user, ?Carbon $date = null): int
    {
        $date ??= Carbon::now();

        return $this->totalBetween($user, $date->clone()->startOfDay(), $date->clone()->endOfDay());
    }

    public function weeklyTotal(User $user, ?Carbon $week = null): int
    {
        $week ??= Carbon::now();

        // Week boundaries follow Carbon's configured week-start (Monday by default).
        return $this->totalBetween($user, $week->clone()->startOfWeek(), $week->clone()->endOfWeek());
    }

    public function monthlyTotal(User $user, ?Carbon $month = null): int
    {
        $month ??= Carbon::now();

        return $this->totalBetween($user, $month->clone()->startOfMonth(), $month->clone()->endOfMonth());
    }

    public function yearlyTotal(User $user, ?Carbon $year = null): int
    {
        $year ??= Carbon::now();

        return $this->totalBetween($user, $year->clone()->startOfYear(), $year->clone()->endOfYear());
    }

    /**
     * Sum a user's XP over an inclusive [$start, $end] window. All period
     * totals share this single boundary convention for consistency.
     */
    private function totalBetween(User $user, Carbon $start, Carbon $end): int
    {
        return (int) XpEvent::where('user_id', $user->id)
            ->whereBetween('awarded_at', [$start, $end])
            ->sum('amount');
    }

    public function allTimeTotal(User $user): int
    {
        return (int) XpEvent::where('user_id', $user->id)->sum('amount');
    }

    public function currentStreak(User $user): int
    {
        $dates = XpEvent::where('user_id', $user->id)
            ->selectRaw('DATE(awarded_at) as day')
            ->groupByRaw('DATE(awarded_at)')
            ->orderByRaw('DATE(awarded_at) DESC')
            ->pluck('day')
            ->map(fn ($d) => Carbon::parse($d)->startOfDay());

        if ($dates->isEmpty()) {
            return 0;
        }

        $mostRecent = $dates->first();
        $yesterday = Carbon::yesterday()->startOfDay();

        if ($mostRecent->lt($yesterday)) {
            return 0;
        }

        $streak = 0;
        $expected = $mostRecent->clone();

        foreach ($dates as $date) {
            if ($date->eq($expected)) {
                $streak++;
                $expected->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    public function maxStreak(User $user): int
    {
        $dates = XpEvent::where('user_id', $user->id)
            ->selectRaw('DATE(awarded_at) as day')
            ->groupByRaw('DATE(awarded_at)')
            ->orderByRaw('DATE(awarded_at) ASC')
            ->pluck('day')
            ->map(fn ($d) => Carbon::parse($d)->startOfDay());

        if ($dates->isEmpty()) {
            return 0;
        }

        $maxStreak = 1;
        $currentStreak = 1;

        for ($i = 1; $i < $dates->count(); $i++) {
            if ($dates[$i]->eq($dates[$i - 1]->clone()->addDay())) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 1;
            }
        }

        return $maxStreak;
    }

    public function dailyStats(User $user): array
    {
        $sub = XpEvent::where('user_id', $user->id)
            ->selectRaw('SUM(amount) as daily_sum')
            ->groupByRaw('DATE(awarded_at)');

        $stats = DB::table(DB::raw("({$sub->toSql()}) as daily_totals"))
            ->mergeBindings($sub->getQuery())
            ->selectRaw('MIN(daily_sum) as min_xp, MAX(daily_sum) as max_xp, ROUND(AVG(daily_sum), 2) as avg_xp')
            ->first();

        if (! $stats || $stats->min_xp === null) {
            return ['min' => 0, 'max' => 0, 'avg' => 0.0];
        }

        return [
            'min' => (int) $stats->min_xp,
            'max' => (int) $stats->max_xp,
            'avg' => (float) $stats->avg_xp,
        ];
    }

    public function level(User $user): int
    {
        return (int) floor($this->allTimeTotal($user) / 100) + 1;
    }

    public function nextLevelXp(User $user): int
    {
        $total = $this->allTimeTotal($user);
        $currentLevel = (int) floor($total / 100);

        return ($currentLevel + 1) * 100 - $total;
    }

    public function activeDays(User $user): int
    {
        return XpEvent::where('user_id', $user->id)
            ->selectRaw('DATE(awarded_at) as day')
            ->groupByRaw('DATE(awarded_at)')
            ->get()
            ->count();
    }

    public function recentActivity(User $user, int $days = 7): array
    {
        $events = XpEvent::where('user_id', $user->id)
            ->where('awarded_at', '>=', Carbon::today()->subDays($days - 1))
            ->selectRaw('DATE(awarded_at) as day, SUM(amount) as xp')
            ->groupByRaw('DATE(awarded_at)')
            ->pluck('xp', 'day');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $result[] = ['date' => $date, 'xp' => (int) ($events[$date] ?? 0)];
        }

        return $result;
    }

    public function sourceBreakdown(User $user): array
    {
        return XpEvent::where('user_id', $user->id)
            ->selectRaw('source, SUM(amount) as total')
            ->groupBy('source')
            ->pluck('total', 'source')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    public function topDay(User $user): ?array
    {
        $result = XpEvent::where('user_id', $user->id)
            ->selectRaw('DATE(awarded_at) as day, SUM(amount) as xp')
            ->groupByRaw('DATE(awarded_at)')
            ->orderByRaw('SUM(amount) DESC')
            ->first();

        if (! $result) {
            return null;
        }

        return ['date' => $result->day, 'xp' => (int) $result->xp];
    }

    public function longestGap(User $user): int
    {
        $dates = XpEvent::where('user_id', $user->id)
            ->selectRaw('DATE(awarded_at) as day')
            ->groupByRaw('DATE(awarded_at)')
            ->orderByRaw('DATE(awarded_at) ASC')
            ->pluck('day')
            ->map(fn ($d) => Carbon::parse($d)->startOfDay());

        if ($dates->count() < 2) {
            return 0;
        }

        $maxGap = 0;
        for ($i = 1; $i < $dates->count(); $i++) {
            $gap = (int) $dates[$i - 1]->diffInDays($dates[$i]) - 1;
            $maxGap = max($maxGap, $gap);
        }

        return $maxGap;
    }

    public function metrics(User $user): array
    {
        return [
            'daily' => $this->dailyTotal($user),
            'weekly' => $this->weeklyTotal($user),
            'monthly' => $this->monthlyTotal($user),
            'yearly' => $this->yearlyTotal($user),
            'all_time' => $this->allTimeTotal($user),
            'streak' => $this->currentStreak($user),
            'max_streak' => $this->maxStreak($user),
            'stats' => $this->dailyStats($user),
            'level' => $this->level($user),
            'next_level_xp' => $this->nextLevelXp($user),
            'active_days' => $this->activeDays($user),
            'recent' => $this->recentActivity($user, 7),
            'source_breakdown' => $this->sourceBreakdown($user),
            'top_day' => $this->topDay($user),
            'longest_gap' => $this->longestGap($user),
        ];
    }
}
