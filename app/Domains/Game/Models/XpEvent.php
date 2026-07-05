<?php

declare(strict_types=1);

namespace App\Domains\Game\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpEvent extends Model
{
    protected $table = 'xp_events';

    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'source', 'amount', 'awarded_at', 'meta'];

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
            'meta' => 'array',
            'amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
