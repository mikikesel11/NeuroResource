<?php

namespace Tests\Feature\Resources;

use App\Domains\Resources\Models\Resource;
use App\Domains\Resources\Models\ResourceUnlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfirmUnlockTest extends TestCase
{
    use RefreshDatabase;

    private function gatedResourceWithPendingUnlock(): array
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/gated.txt', 'secret');

        $resource = Resource::create([
            'slug' => 'gated-guide',
            'title' => ['en' => 'Gated Guide'],
            'summary' => ['en' => 'Summary.'],
            'type' => 'pdf',
            'file_path' => 'resources/gated.txt',
            'access' => 'email',
            'published_at' => now(),
        ]);

        $unlock = ResourceUnlock::create([
            'resource_id' => $resource->id,
            'email' => 'person@example.com',
            'token' => 'test-token-123',
        ]);

        return [$resource, $unlock];
    }

    public function test_confirming_marks_capture_confirmed_and_unlocks_download(): void
    {
        [$resource, $unlock] = $this->gatedResourceWithPendingUnlock();

        // Before confirming, the gate blocks the download.
        $this->get(route('resources.download', $resource->slug))->assertForbidden();

        // Clicking the emailed link confirms and redirects to the resource page.
        $this->get(route('resources.confirm', 'test-token-123'))
            ->assertRedirect(route('resources.show', $resource->slug));

        $this->assertNotNull($unlock->refresh()->confirmed_at);

        // The same session may now download.
        $this->get(route('resources.download', $resource->slug))
            ->assertOk()
            ->assertDownload();
    }

    public function test_invalid_confirmation_token_404s(): void
    {
        $this->get(route('resources.confirm', 'nope'))->assertNotFound();
    }
}
