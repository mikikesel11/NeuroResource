<?php

declare(strict_types=1);

namespace Tests\Feature\Resources;

use App\Domains\Resources\Mail\ConfirmResourceUnlock;
use App\Domains\Resources\Models\Resource;
use App\Livewire\Resources\ResourcePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class UnlockTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('resource-unlock|person@example.com|127.0.0.1');

        parent::tearDown();
    }

    private function emailGatedResource(): Resource
    {
        return Resource::factory()->emailGated()->create([
            'slug' => 'gated-guide',
            'title' => ['en' => 'Gated Guide'],
            'summary' => ['en' => 'Summary.'],
            'type' => 'pdf',
            'file_path' => 'resources/gated.txt',
            'published_at' => now(),
        ]);
    }

    public function test_submitting_email_sends_confirmation_without_unlocking_yet(): void
    {
        Mail::fake();
        $resource = $this->emailGatedResource();

        Livewire::test(ResourcePage::class, ['slug' => $resource->slug])
            ->assertSee('Enter your Email')           // gate shown first
            ->set('email', 'person@example.com')
            ->call('unlock')
            ->assertSet('pendingConfirmation', true)
            ->assertHasNoErrors()
            ->assertSee('confirm your Email')          // pending state
            ->assertDontSee('Download Resource');      // NOT unlocked yet

        // A pending (unconfirmed) capture was stored and exactly one email sent.
        $this->assertDatabaseHas('resource_unlocks', [
            'resource_id' => $resource->id,
            'email' => 'person@example.com',
            'confirmed_at' => null,
        ]);
        Mail::assertSent(ConfirmResourceUnlock::class);
        Mail::assertSentCount(1);
    }

    public function test_unlock_requires_a_valid_email(): void
    {
        Mail::fake();
        $resource = $this->emailGatedResource();

        Livewire::test(ResourcePage::class, ['slug' => $resource->slug])
            ->set('email', 'not-an-email')
            ->call('unlock')
            ->assertHasErrors(['email'])
            ->assertSet('pendingConfirmation', false);

        $this->assertDatabaseCount('resource_unlocks', 0);
        Mail::assertNothingSent();
    }

    public function test_unlock_is_rate_limited_per_ip_and_email(): void
    {
        Mail::fake();
        $resource = $this->emailGatedResource();
        $maxAttempts = (int) config('neuroresource.unlock_max_attempts');

        $component = Livewire::test(ResourcePage::class, ['slug' => $resource->slug])
            ->set('email', 'person@example.com');

        // Exhaust the allowed attempts — each sends one mail.
        for ($i = 0; $i < $maxAttempts; $i++) {
            $component->call('unlock')->assertHasNoErrors();
        }

        Mail::assertSentCount($maxAttempts);

        // The next attempt is throttled: a friendly notice, no further mail.
        $component->call('unlock')->assertHasErrors(['email']);

        Mail::assertSentCount($maxAttempts);
        $this->assertDatabaseCount('resource_unlocks', $maxAttempts);
    }
}
