<?php

declare(strict_types=1);

namespace Tests\Feature\Resources;

use App\Domains\Resources\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::disk('public')->put('resources/file.txt', 'hello');
    }

    private function resource(string $access, array $overrides = []): Resource
    {
        return Resource::factory()->create(array_merge([
            'slug' => 'a-resource',
            'title' => ['en' => 'A Resource'],
            'summary' => ['en' => 'Summary.'],
            'type' => 'printable',
            'file_path' => 'resources/file.txt',
            'access' => $access,
            'published_at' => now(),
        ], $overrides));
    }

    public function test_free_resource_downloads_and_increments_count(): void
    {
        $resource = $this->resource('free');

        $this->get(route('resources.download', $resource->slug))
            ->assertOk()
            ->assertDownload();

        // Queue is sync in tests, so the async counter has run.
        $this->assertSame(1, $resource->refresh()->download_count);
    }

    public function test_email_gated_resource_blocks_anonymous_download(): void
    {
        $resource = $this->resource('email');

        $this->get(route('resources.download', $resource->slug))->assertForbidden();
        $this->assertSame(0, $resource->refresh()->download_count);
    }

    public function test_email_gated_resource_allows_authenticated_download(): void
    {
        $resource = $this->resource('email');

        $this->actingAs(User::factory()->create())
            ->get(route('resources.download', $resource->slug))
            ->assertOk()
            ->assertDownload();
    }

    public function test_link_resource_redirects_to_external_url(): void
    {
        $resource = $this->resource('free', [
            'type' => 'link',
            'file_path' => null,
            'external_url' => 'https://example.org/reading-list',
        ]);

        $this->get(route('resources.download', $resource->slug))
            ->assertRedirect('https://example.org/reading-list');
    }

    public function test_unpublished_resource_download_404s(): void
    {
        $resource = $this->resource('free', ['published_at' => null]);

        $this->get(route('resources.download', $resource->slug))->assertNotFound();
    }
}
