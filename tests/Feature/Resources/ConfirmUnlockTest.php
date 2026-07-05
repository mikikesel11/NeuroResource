<?php

declare(strict_types=1);

namespace Tests\Feature\Resources;

use App\Domains\Resources\Models\Resource;
use App\Domains\Resources\Models\ResourceUnlock;
use App\Domains\Resources\Support\ResourceGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ConfirmUnlockTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array{0: \App\Domains\Resources\Models\Resource, 1: ResourceUnlock}
     */
    private function gatedResourceWithPendingUnlock(): array
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/gated.txt', 'secret');

        $resource = Resource::factory()->emailGated()->create([
            'slug' => 'gated-guide',
            'title' => ['en' => 'Gated Guide'],
            'summary' => ['en' => 'Summary.'],
            'type' => 'pdf',
            'file_path' => 'resources/gated.txt',
            'published_at' => now(),
        ]);

        $unlock = ResourceUnlock::create([
            'resource_id' => $resource->id,
            'email' => 'person@example.com',
            'token' => 'test-token-123',
        ]);

        return [$resource, $unlock];
    }

    private function signedConfirmUrl(string $token): string
    {
        return URL::temporarySignedRoute('resources.confirm', now()->addDay(), ['token' => $token]);
    }

    public function test_confirming_marks_capture_confirmed_and_unlocks_download(): void
    {
        [$resource, $unlock] = $this->gatedResourceWithPendingUnlock();

        // Before confirming, the gate blocks the download.
        $this->get(route('resources.download', $resource->slug))->assertForbidden();

        // Clicking the signed emailed link confirms and redirects to the page.
        $this->get($this->signedConfirmUrl('test-token-123'))
            ->assertRedirect(route('resources.show', $resource->slug));

        $this->assertNotNull($unlock->refresh()->confirmed_at);

        // The same session may now download.
        $this->get(route('resources.download', $resource->slug))
            ->assertOk()
            ->assertDownload();
    }

    public function test_revisiting_a_confirmed_link_does_not_duplicate_session_slug(): void
    {
        [$resource] = $this->gatedResourceWithPendingUnlock();

        // First visit confirms and grants access.
        $this->get($this->signedConfirmUrl('test-token-123'))
            ->assertRedirect(route('resources.show', $resource->slug));

        // Re-visiting the same link must not append the slug again.
        $this->get($this->signedConfirmUrl('test-token-123'))
            ->assertRedirect(route('resources.show', $resource->slug));

        $this->assertSame(
            [$resource->slug],
            session(ResourceGate::SESSION_KEY),
        );
    }

    public function test_tampered_confirmation_link_is_forbidden(): void
    {
        $this->gatedResourceWithPendingUnlock();

        // A valid URL with the signature stripped/altered must be rejected 403.
        $tampered = $this->signedConfirmUrl('test-token-123').'&signature=deadbeef';

        $this->get($tampered)->assertForbidden();
    }

    public function test_unsigned_confirmation_link_is_forbidden(): void
    {
        $this->gatedResourceWithPendingUnlock();

        // The old-style unsigned link must no longer work.
        $this->get(route('resources.confirm', ['token' => 'test-token-123']))
            ->assertForbidden();
    }

    public function test_expired_confirmation_link_is_forbidden(): void
    {
        $this->gatedResourceWithPendingUnlock();

        Carbon::setTestNow('2026-01-01 12:00:00');
        $url = URL::temporarySignedRoute('resources.confirm', now()->addHours(24), ['token' => 'test-token-123']);

        // Jump past the TTL — the signature is now expired.
        Carbon::setTestNow('2026-01-02 13:00:00');

        $this->get($url)->assertForbidden();
    }

    public function test_valid_signature_with_unknown_token_404s(): void
    {
        $this->get($this->signedConfirmUrl('nope'))->assertNotFound();
    }
}
