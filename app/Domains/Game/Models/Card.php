<?php

namespace App\Domains\Game\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    protected $fillable = ['name', 'description', 'deck', 'xp_earned', 'is_active'];

    protected function casts(): array
    {
        return [
            'xp_earned' => 'integer',
            'is_active' => 'boolean',
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
