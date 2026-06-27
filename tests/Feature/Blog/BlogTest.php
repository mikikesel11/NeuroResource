<?php

namespace Tests\Feature\Blog;

use App\Domains\Content\Models\Post;
use App\Domains\Content\Models\Tag;
use App\Livewire\Blog\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(string $title, array $overrides = []): Post
    {
        return Post::create(array_merge([
            'slug' => str($title)->slug()->value(),
            'title' => ['en' => $title],
            'excerpt' => ['en' => "Excerpt for $title."],
            'body' => ['en' => "Body for **$title**."],
            'status' => 'published',
            'reading_minutes' => 3,
            'author_id' => User::factory()->create()->id,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    public function test_index_lists_published_posts_only(): void
    {
        $this->makePost('Published Post');
        $this->makePost('Draft Post', ['status' => 'draft', 'published_at' => null]);
        $this->makePost('Future Post', ['published_at' => now()->addWeek()]);

        Livewire::test(Index::class)
            ->assertSee('Published Post')
            ->assertDontSee('Draft Post')
            ->assertDontSee('Future Post');
    }

    public function test_index_filters_by_tag(): void
    {
        $focus = Tag::create(['slug' => 'focus', 'name' => ['en' => 'Focus']]);
        $sensory = Tag::create(['slug' => 'sensory', 'name' => ['en' => 'Sensory']]);

        $this->makePost('Focus Post')->tags()->attach($focus);
        $this->makePost('Sensory Post')->tags()->attach($sensory);

        Livewire::test(Index::class)
            ->set('tag', 'focus')
            ->assertSee('Focus Post')
            ->assertDontSee('Sensory Post');
    }

    public function test_post_page_renders_body_and_meta(): void
    {
        $this->makePost('A Readable Post');

        $response = $this->get(route('blog.show', 'a-readable-post'));

        $response->assertOk();
        $response->assertSee('A Readable Post');
        $response->assertSee('min read');
        $response->assertSee('<strong>A Readable Post</strong>', false); // Markdown rendered
    }

    public function test_draft_post_page_404s(): void
    {
        $this->makePost('Hidden Post', ['status' => 'draft', 'published_at' => null]);

        $this->get(route('blog.show', 'hidden-post'))->assertNotFound();
    }

    public function test_rss_feed_lists_published_posts(): void
    {
        $this->makePost('Feed Post');
        $this->makePost('Draft For Feed', ['status' => 'draft', 'published_at' => null]);

        $response = $this->get(route('feeds.blog'));

        $response->assertOk();
        $response->assertSee('Feed Post');
        $response->assertDontSee('Draft For Feed');
        // RSS is served as application/xml; assert it's XML and a valid <rss> doc.
        $this->assertStringContainsString('xml', strtolower($response->headers->get('content-type') ?? ''));
        $response->assertSee('<rss', false);
    }
}
