<?php

declare(strict_types=1);

namespace Tests\Unit\Game;

use App\Domains\Game\Support\DeckAccess;
use App\Models\User;
use Tests\TestCase;

class DeckAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['neuroresource.decks' => [
            'focus' => [
                'title' => 'Focus',
                'prompt' => 'Help getting started',
                'access' => 'free',
                'back_image' => null,
                'bg' => 'from-blue-50 to-blue-100',
                'accent' => 'text-blue-800',
                'border' => 'border-blue-200',
            ],
            'premium' => [
                'title' => 'Premium',
                'prompt' => 'Exclusive content',
                'access' => 'paid',
                'back_image' => null,
                'bg' => 'from-purple-50 to-purple-100',
                'accent' => 'text-purple-800',
                'border' => 'border-purple-200',
            ],
        ]]);
    }

    public function test_allows_free_deck_for_authenticated_user(): void
    {
        $user = User::factory()->make();

        $this->assertTrue(DeckAccess::allows($user, 'focus'));
    }

    public function test_denies_non_free_deck_for_authenticated_user(): void
    {
        $user = User::factory()->make();

        $this->assertFalse(DeckAccess::allows($user, 'premium'));
    }

    public function test_denies_unknown_deck(): void
    {
        $user = User::factory()->make();

        $this->assertFalse(DeckAccess::allows($user, 'nonexistent'));
    }

    public function test_denies_null_user(): void
    {
        $this->assertFalse(DeckAccess::allows(null, 'focus'));
    }

    public function test_accessible_to_returns_only_free_decks(): void
    {
        $user = User::factory()->make();

        $accessible = DeckAccess::accessibleTo($user);

        $this->assertArrayHasKey('focus', $accessible);
        $this->assertArrayNotHasKey('premium', $accessible);
    }

    public function test_accessible_to_returns_empty_for_null_user(): void
    {
        $accessible = DeckAccess::accessibleTo(null);

        $this->assertEmpty($accessible);
    }
}
