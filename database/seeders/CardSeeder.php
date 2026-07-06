<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Game\Models\Card;
use Illuminate\Database\Seeder;

class CardSeeder extends Seeder
{
    /**
     * Seed a small set of cards across the shipped decks so the Card Draw —
     * including the opt-in timer flow — is explorable locally. At least one
     * card carries a `timer_minutes` value and subtasks.
     */
    public function run(): void
    {
        $cards = [
            [
                'name' => 'Single Focus Sprint',
                'description' => 'Pick one task and give it your full attention for a short stretch.',
                'subtasks' => ['Choose the task', 'Clear your space', 'Begin gently'],
                'deck' => 'focus',
                'xp_earned' => 15,
                'is_active' => true,
                'timer_minutes' => 20,
            ],
            [
                'name' => 'Notice the Room',
                'description' => 'Name three things you can see and one thing you can hear.',
                'subtasks' => null,
                'deck' => 'focus',
                'xp_earned' => 10,
                'is_active' => true,
                'timer_minutes' => null,
            ],
            [
                'name' => 'Slow Breath',
                'description' => 'Take a few slow breaths. In for four, out for six.',
                'subtasks' => ['Breathe in', 'Breathe out'],
                'deck' => 'calm',
                'xp_earned' => 10,
                'is_active' => true,
                'timer_minutes' => 5,
            ],
            [
                'name' => 'Stand Tall',
                'description' => 'Do one small brave thing you have been putting off.',
                'subtasks' => null,
                'deck' => 'brave',
                'xp_earned' => 20,
                'is_active' => true,
                'timer_minutes' => null,
            ],
        ];

        foreach ($cards as $card) {
            Card::firstOrCreate(
                ['deck' => $card['deck'], 'name' => $card['name']],
                $card,
            );
        }
    }
}
