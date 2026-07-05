<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Resources\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Domains\Resources\Models\Resource>
 *
 * `access` (authorization gate) and `download_count` are intentionally NOT
 * mass-assignable on the model. The factory sets them via forceFill so tests
 * and seeders can build fixtures without re-opening mass assignment.
 */
class ResourceFactory extends Factory
{
    /** @var list<string> Guarded columns applied via forceFill, not mass assignment. */
    private const GUARDED_COLUMNS = ['access', 'download_count'];

    protected $model = Resource::class;

    public function definition(): array
    {
        $title = rtrim($this->faker->unique()->sentence(3), '.');

        return [
            'slug' => str($title)->slug()->value(),
            'title' => ['en' => $title],
            'summary' => ['en' => $this->faker->sentence()],
            'type' => 'pdf',
            'file_path' => 'resources/'.$this->faker->slug().'.txt',
            'external_url' => null,
            'published_at' => now(),
            'access' => 'free',
            'download_count' => 0,
        ];
    }

    /** Email-gated access tier. */
    public function emailGated(): static
    {
        return $this->state(['access' => 'email']);
    }

    /** Free access tier (default). */
    public function free(): static
    {
        return $this->state(['access' => 'free']);
    }

    /**
     * Build the model, applying guarded columns via forceFill since they are
     * excluded from $fillable and would otherwise be dropped.
     */
    public function newModel(array $attributes = []): Resource
    {
        $guarded = array_intersect_key($attributes, array_flip(self::GUARDED_COLUMNS));

        /** @var \App\Domains\Resources\Models\Resource $model */
        $model = parent::newModel(array_diff_key($attributes, $guarded));

        if ($guarded !== []) {
            $model->forceFill($guarded);
        }

        return $model;
    }
}
