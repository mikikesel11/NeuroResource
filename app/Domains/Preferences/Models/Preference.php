<?php

namespace App\Domains\Preferences\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Preference extends Model
{
    protected $fillable = [
        'user_id', 'cookie_id', 'theme', 'text_scale',
        'line_height', 'font', 'reduce_motion', 'locale',
    ];

    protected $casts = [
        'text_scale' => 'float',
        'line_height' => 'float',
        'reduce_motion' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
