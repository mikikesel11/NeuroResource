<?php

declare(strict_types=1);

namespace App\Domains\Game\Services;

use App\Domains\Game\Models\Card;
use App\Domains\Game\Models\UserCardPull;
use App\Domains\Game\Models\XpEvent;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CardService
{
    public function __construct(private readonly XpService $xp) {}

    public function draw(User $user, string $deck): array
    {
        $cards = $this->cardsWithPullCounts($user, $deck);

        if ($cards->isEmpty()) {
            throw new RuntimeException("No active cards found in deck: {$deck}");
        }

        $minPulls = (int) $cards->first()->user_pull_count;
        $eligible = $cards->filter(fn ($c) => (int) $c->user_pull_count === $minPulls);
        $card = $eligible->random();

        $pull = $this->recordPull($user, $card);

        // XP is awarded on completion, not on draw — see complete().
        return [
            'card' => $card,
            'pull_count' => $pull->pull_count,
        ];
    }

    /**
     * Award the card's XP once the player marks it done. Uses an atomic
     * UPDATE WHERE completed_at IS NULL as the database-level guard so that
     * concurrent Livewire requests carrying the same stale snapshot cannot
     * both award XP. Returns null if the card was already completed this pull.
     */
    public function complete(User $user, Card $card): ?XpEvent
    {
        return DB::transaction(function () use ($user, $card) {
            $updated = DB::table('user_card_pulls')
                ->where('user_id', $user->id)
                ->where('card_id', $card->id)
                ->whereNull('completed_at')
                ->update(['completed_at' => now()]);

            if ($updated === 0) {
                return null;
            }

            return $this->xp->award($user, "card:{$card->deck}-{$card->name}", $card->xp_earned);
        });
    }

    public function pullCountsForUser(User $user, string $deck): Collection
    {
        return UserCardPull::whereHas('card', fn ($q) => $q->where('deck', $deck))
            ->where('user_id', $user->id)
            ->pluck('pull_count', 'card_id');
    }

    public function historyForUser(User $user, string $deck, int $limit = 20): Collection
    {
        return UserCardPull::with('card')
            ->whereHas('card', fn ($q) => $q->where('deck', $deck))
            ->where('user_id', $user->id)
            ->orderByDesc('last_pulled_at')
            ->limit($limit)
            ->get();
    }

    private function cardsWithPullCounts(User $user, string $deck): Collection
    {
        return Card::where('cards.deck', $deck)
            ->where('cards.is_active', true)
            ->leftJoin('user_card_pulls', function ($join) use ($user) {
                $join->on('user_card_pulls.card_id', '=', 'cards.id')
                    ->where('user_card_pulls.user_id', '=', $user->id);
            })
            ->select('cards.*')
            ->selectRaw('COALESCE(user_card_pulls.pull_count, 0) AS user_pull_count')
            ->orderByRaw('COALESCE(user_card_pulls.pull_count, 0)')
            ->get();
    }

    private function recordPull(User $user, Card $card): UserCardPull
    {
        $keys = ['user_id' => $user->id, 'card_id' => $card->id];

        try {
            return DB::transaction(fn () => $this->lockAndIncrement($keys));
        } catch (UniqueConstraintViolationException) {
            // A concurrent first-draw won the INSERT race; the row now exists,
            // so re-run the locked increment against the existing row.
            return DB::transaction(fn () => $this->lockAndIncrement($keys));
        }
    }

    /**
     * @param  array{user_id: int, card_id: int}  $keys
     */
    private function lockAndIncrement(array $keys): UserCardPull
    {
        $pull = UserCardPull::where($keys)->lockForUpdate()->first()
            ?? UserCardPull::create([...$keys, 'pull_count' => 0]);

        $pull->increment('pull_count', 1, ['last_pulled_at' => now(), 'completed_at' => null]);

        return $pull->fresh();
    }
}
