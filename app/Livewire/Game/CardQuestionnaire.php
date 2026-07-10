<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Domains\Game\Support\DeckAccess;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.public-layout', ['title' => 'Choose Your Deck'])]
class CardQuestionnaire extends Component
{
    public function choose(string $deck): mixed
    {
        abort_unless(DeckAccess::allows(auth()->user(), $deck), 404);

        return redirect()->route('cards.draw', ['deck' => $deck]);
    }

    public function render(): mixed
    {
        return view('livewire.game.card-questionnaire', [
            'decks' => DeckAccess::accessibleTo(auth()->user()),
        ]);
    }
}
