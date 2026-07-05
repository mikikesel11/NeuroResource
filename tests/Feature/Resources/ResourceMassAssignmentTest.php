<?php

declare(strict_types=1);

namespace Tests\Feature\Resources;

use App\Domains\Resources\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_is_not_mass_assignable(): void
    {
        // Simulates untrusted request input trying to bypass the email gate.
        $resource = Resource::create([
            'slug' => 'sneaky',
            'title' => ['en' => 'Sneaky'],
            'summary' => ['en' => 'Summary.'],
            'type' => 'pdf',
            'file_path' => 'resources/x.txt',
            'access' => 'free',
            'published_at' => now(),
        ]);

        // `access` was ignored on create (not in $fillable) and falls back to
        // the column default ('free'), never a caller-supplied value.
        $this->assertSame('free', $resource->refresh()->access);
        $this->assertNotContains('access', $resource->getFillable());
    }

    public function test_download_count_is_not_mass_assignable(): void
    {
        $resource = Resource::create([
            'slug' => 'counter',
            'title' => ['en' => 'Counter'],
            'summary' => ['en' => 'Summary.'],
            'type' => 'pdf',
            'file_path' => 'resources/x.txt',
            'download_count' => 9999,
            'published_at' => now(),
        ]);

        // `download_count` was ignored and stays at the column default (0).
        $this->assertSame(0, $resource->refresh()->download_count);
    }

    public function test_factory_can_still_set_guarded_columns_explicitly(): void
    {
        $resource = Resource::factory()->emailGated()->create(['download_count' => 5]);

        $this->assertSame('email', $resource->refresh()->access);
        $this->assertSame(5, $resource->download_count);
    }
}
