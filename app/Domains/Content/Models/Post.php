<?php

namespace App\Domains\Content\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;
use Spatie\Translatable\HasTranslations;

class Post extends Model implements Feedable
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'excerpt', 'body'];

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body', 'status',
        'reading_minutes', 'author_id', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** Only posts marked published with a past/now publish date. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create()
            ->id((string) $this->id)
            ->title($this->title)
            ->summary($this->excerpt ?? '')
            ->updated($this->published_at ?? $this->updated_at)
            ->link(route('blog.show', $this->slug))
            ->authorName($this->author?->name ?? config('app.name'));
    }

    /** @return Collection<int, Post> */
    public static function getFeedItems(): Collection
    {
        return static::published()->latest('published_at')->get();
    }
}
