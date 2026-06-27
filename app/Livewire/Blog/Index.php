<?php

namespace App\Livewire\Blog;

use App\Domains\Content\Models\Post;
use App\Domains\Content\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.public-layout', ['title' => 'Blog'])]
class Index extends Component
{
    // Bound to the query string so a filtered Blog is shareable/bookmarkable.
    #[Url]
    public ?string $tag = null;

    public function render()
    {
        $posts = Post::published()
            ->with(['tags', 'author'])
            ->when($this->tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('slug', $this->tag)))
            ->latest('published_at')
            ->get();

        return view('livewire.blog.index', [
            'posts' => $posts,
            'tags' => Tag::whereHas('posts', fn ($q) => $q->published())->orderBy('slug')->get(),
        ]);
    }
}
