<?php

namespace Tests\Feature\Resources;

use App\Domains\Resources\Mail\ConfirmResourceUnlock;
use App\Domains\Resources\Models\Resource;
use App\Livewire\Resources\ResourcePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class UnlockTest extends TestCase
{
    use RefreshDatabase;

    private function emailGatedResource(): Resource
    {
        return Resource::create([
            'slug' => 'gated-guide',
            'title' => ['en' => 'Gated Guide'],
            'summary' => ['en' => 'Summary.'],
            'type' => 'pdf',
            'file_path' => 'resources/gated.txt',
            'access' => 'email',
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
            ->assertSee('confirm your Email')          // pending state
            ->assertDontSee('Download Resource');      // NOT unlocked yet

        // A pending (unconfirmed) capture was stored and the email was sent.
        $this->assertDatabaseHas('resource_unlocks', [
            'resource_id' => $resource->id,
            'email' => 'person@example.com',
            'confirmed_at' => null,
        ]);
        Mail::assertSent(ConfirmResourceUnlock::class);
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
}
