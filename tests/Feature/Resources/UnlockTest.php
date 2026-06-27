<?php

namespace Tests\Feature\Resources;

use App\Domains\Resources\Models\Resource;
use App\Livewire\Resources\ResourcePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_email_unlock_records_capture_and_reveals_download(): void
    {
        $resource = $this->emailGatedResource();

        Livewire::test(ResourcePage::class, ['slug' => $resource->slug])
            ->assertSee('Enter your Email')      // gate shown first
            ->set('email', 'person@example.com')
            ->call('unlock')
            ->assertSet('justUnlocked', true)
            ->assertSee('Download Resource');

        $this->assertDatabaseHas('resource_unlocks', [
            'resource_id' => $resource->id,
            'email' => 'person@example.com',
        ]);
    }

    public function test_unlock_requires_a_valid_email(): void
    {
        $resource = $this->emailGatedResource();

        Livewire::test(ResourcePage::class, ['slug' => $resource->slug])
            ->set('email', 'not-an-email')
            ->call('unlock')
            ->assertHasErrors(['email']);

        $this->assertDatabaseCount('resource_unlocks', 0);
    }
}
