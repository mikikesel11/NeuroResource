<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Domains\Game\Services\CardService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.public-layout', ['title' => 'Card Draw'])]
class CardDraw extends Component
{
    public string $deck;

    public string $phase = 'idle';

    public ?array $result = null;

    public array $checkedSubtasks = [];

    public bool $cardDone = false;

    public array $deckTheme = [];

    public function mount(string $deck): void
    {
        abort_unless(config("neuroresource.decks.{$deck}"), 404);

        $this->deck = $deck;
        $this->deckTheme = config("neuroresource.decks.{$deck}", [
            'back_image' => null,
            'bg' => 'from-[var(--ns-surface)] to-[var(--ns-surface)]',
            'accent' => 'text-[var(--ns-text)]',
            'border' => 'border-[var(--ns-border)]',
        ]);
    }

    public function draw(CardService $cards): void
    {
        $result = $cards->draw(auth()->user(), $this->deck);

        $this->result = [
            'card' => $result['card'],
            'pull_count' => $result['pull_count'],
            'xp_awarded' => 0, // Awarded on completion — see completeCard().
        ];

        $this->cardDone = false;
        $this->phase = 'revealed';

        $this->dispatch('card-drawn');
    }

    public function toggleSubtask(int $index, CardService $cards): void
    {
        if (in_array($index, $this->checkedSubtasks)) {
            $this->checkedSubtasks = array_values(
                array_filter($this->checkedSubtasks, fn ($i) => $i !== $index)
            );
        } else {
            $this->checkedSubtasks[] = $index;
        }

        // Checking off the final subtask completes the card (awards XP, stops the timer).
        $subtasks = $this->result['card']->subtasks ?? [];
        if ($subtasks !== [] && count($this->checkedSubtasks) === count($subtasks)) {
            $this->completeCard($cards);
        }
    }

    public function markDone(CardService $cards): void
    {
        $this->completeCard($cards);
    }

    /**
     * Mark the current card done: award its XP exactly once, then flag it
     * complete so the client timer cancels. Guarded because three paths call
     * it (the card button, the reminder button, and checking the last subtask).
     */
    private function completeCard(CardService $cards): void
    {
        if ($this->cardDone || $this->result === null) {
            return;
        }

        $event = $cards->complete(auth()->user(), $this->result['card']);

        $this->result['xp_awarded'] = $event?->amount ?? 0;
        $this->cardDone = true;
    }

    public function resetDraw(): void
    {
        $this->result = null;
        $this->checkedSubtasks = [];
        $this->cardDone = false;
        $this->phase = 'idle';
    }

    public function render()
    {
        return view('livewire.game.card-draw');
    }
}
