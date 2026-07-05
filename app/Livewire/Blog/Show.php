<?php

declare(strict_types=1);

namespace App\Livewire\Blog;

use App\Domains\Content\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.public-layout', ['title' => 'Blog'])]
class Show extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        abort_unless(Post::published()->where('slug', $slug)->exists(), 404);

        $this->slug = $slug;
    }

    public function render()
    {
        $post = Post::published()
            ->with(['tags', 'author'])
            ->where('slug', $this->slug)
            ->firstOrFail();

        return view('livewire.blog.show', ['post' => $post]);
    }
}
