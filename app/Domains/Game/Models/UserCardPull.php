<?php

declare(strict_types=1);

namespace App\Domains\Game\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCardPull extends Model
{
    protected $fillable = ['user_id', 'card_id', 'pull_count', 'last_pulled_at'];

    protected function casts(): array
    {
        return [
            'pull_count' => 'integer',
            'last_pulled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
