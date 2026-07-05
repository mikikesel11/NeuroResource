<?php

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
            'xp_awarded' => $result['xp_awarded'],
        ];

        $this->phase = 'revealed';

        $this->dispatch('card-drawn');
    }

    public function toggleSubtask(int $index): void
    {
        if (in_array($index, $this->checkedSubtasks)) {
            $this->checkedSubtasks = array_values(
                array_filter($this->checkedSubtasks, fn ($i) => $i !== $index)
            );
        } else {
            $this->checkedSubtasks[] = $index;
        }
    }

    public function resetDraw(): void
    {
        $this->result = null;
        $this->checkedSubtasks = [];
        $this->phase = 'idle';
    }

    public function render()
    {
        return view('livewire.game.card-draw');
    }
}
