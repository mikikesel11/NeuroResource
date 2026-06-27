<?php

namespace App\Domains\Game\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameProgress extends Model
{
    protected $table = 'game_progress';

    protected $fillable = ['user_id', 'story_id', 'scene_id', 'state_json'];

    protected $casts = [
        'state_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
