<?php

namespace App\Domains\Game\Services;

use App\Domains\Game\Models\Card;
use App\Domains\Game\Models\UserCardPull;
use App\Models\User;
use Illuminate\Support\Collection;
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
        $xpEvent = $this->xp->award($user, "card:{$deck}-{$card->name}", $card->xp_earned);

        return [
            'card' => $card,
            'pull_count' => $pull->pull_count,
            'xp_awarded' => $xpEvent?->amount ?? 0,
        ];
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
        $pull = UserCardPull::firstOrCreate(
            ['user_id' => $user->id, 'card_id' => $card->id],
            ['pull_count' => 0]
        );

        $pull->increment('pull_count', 1, ['last_pulled_at' => now()]);

        return $pull->fresh();
    }
}
