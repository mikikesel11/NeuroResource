<?php

namespace Tests\Feature\Resources;

use App\Domains\Content\Models\Tag;
use App\Domains\Resources\Models\Resource;
use App\Livewire\Resources\Library;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    private function resource(string $title, ?string $publishedAt = 'now'): Resource
    {
        return Resource::create([
            'slug' => str($title)->slug()->value(),
            'title' => ['en' => $title],
            'summary' => ['en' => "Summary for $title."],
            'type' => 'printable',
            'access' => 'free',
            'published_at' => $publishedAt === 'now' ? now() : $publishedAt,
        ]);
    }

    public function test_library_lists_published_resources_only(): void
    {
        $this->resource('Published Guide');
        $this->resource('Draft Guide', publishedAt: null);

        Livewire::test(Library::class)
            ->assertSee('Published Guide')
            ->assertDontSee('Draft Guide');
    }

    public function test_library_filters_by_tag(): void
    {
        $focus = Tag::create(['slug' => 'focus', 'name' => ['en' => 'Focus']]);
        $sensory = Tag::create(['slug' => 'sensory', 'name' => ['en' => 'Sensory']]);

        $this->resource('Focus Guide')->tags()->attach($focus);
        $this->resource('Sensory Guide')->tags()->attach($sensory);

        Livewire::test(Library::class)
            ->set('tag', 'focus')
            ->assertSee('Focus Guide')
            ->assertDontSee('Sensory Guide');
    }
}
