<?php

declare(strict_types=1);

namespace App\Domains\Resources\Models;

use App\Domains\Content\Models\Tag;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'summary'];

    // `access` drives the authorization gate and `download_count` is an internal
    // counter — neither may ever be set from request input, so both are omitted
    // from $fillable and must be assigned explicitly.
    protected $fillable = [
        'slug', 'title', 'summary', 'type',
        'file_path', 'external_url', 'published_at',
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

    /** Custom-namespaced model, so point the factory resolver explicitly. */
    protected static function newFactory(): Factory
    {
        return ResourceFactory::new();
    }
}
