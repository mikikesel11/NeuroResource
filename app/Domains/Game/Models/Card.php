<?php

declare(strict_types=1);

namespace App\Domains\Game\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    protected $fillable = ['name', 'description', 'subtasks', 'deck', 'xp_earned', 'is_active', 'timer_minutes'];

    protected function casts(): array
    {
        return [
            'xp_earned' => 'integer',
            'is_active' => 'boolean',
            'subtasks' => 'array',
            'timer_minutes' => 'integer',
        ];
    }

    public function userPulls(): HasMany
    {
        return $this->hasMany(UserCardPull::class);
    }

    public function scopeDeck(Builder $query, string $deck): Builder
    {
        return $query->where('deck', $deck);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
