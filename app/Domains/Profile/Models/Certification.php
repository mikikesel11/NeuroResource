<?php

declare(strict_types=1);

namespace App\Domains\Profile\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    protected $fillable = [
        'profile_id', 'name', 'issuer', 'issued_on', 'expires_on',
        'credential_url', 'badge_path', 'sort_order',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'expires_on' => 'date',
    ];

    /**
     * Only store credential_url values that use http:// or https://.
     * Any other scheme (javascript:, data:, etc.) is rejected and stored as null.
     */
    protected function credentialUrl(): Attribute
    {
        return Attribute::make(
            set: static function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }

                $lower = strtolower($value);

                return str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')
                    ? $value
                    : null;
            },
        );
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
