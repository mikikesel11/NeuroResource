<?php

namespace App\Domains\Resources\Models;

use App\Domains\Content\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Resource extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'summary'];

    protected $fillable = [
        'slug', 'title', 'summary', 'type',
        'file_path', 'external_url', 'access',
        'download_count', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'download_count' => 'integer',
    ];

    public function isEmailGated(): bool
    {
        return $this->access === 'email';
    }

    /** Only resources with a past/now publish date. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(ResourceUnlock::class);
    }
}
