<?php

declare(strict_types=1);

namespace App\Domains\Profile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Profile extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['headline', 'bio'];

    protected $fillable = ['name', 'headline', 'bio', 'avatar_path'];

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class)->orderBy('sort_order');
    }
}
