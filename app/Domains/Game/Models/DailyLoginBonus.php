<?php

declare(strict_types=1);

namespace App\Domains\Game\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A pure idempotency guard for the daily login XP bonus. The unique
 * constraint on (user_id, awarded_date) is what actually prevents a
 * double award under concurrent requests — see XpService::award().
 */
class DailyLoginBonus extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'awarded_date'];

    protected function casts(): array
    {
        return [
            'awarded_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
