<?php

namespace App\Livewire\Resources;

use App\Domains\Content\Models\Tag;
use App\Domains\Resources\Models\Resource;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.public-layout', ['title' => 'Resource Library'])]
class Library extends Component
{
    // Bound to the query string so a filtered Library is shareable/bookmarkable.
    #[Url]
    public ?string $tag = null;

    public function render()
    {
        $resources = Resource::published()
            ->with('tags')
            ->when($this->tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('slug', $this->tag)))
            ->latest('published_at')
            ->get();

        return view('livewire.resources.library', [
            'resources' => $resources,
            'tags' => Tag::whereHas('resources', fn ($q) => $q->published())->orderBy('slug')->get(),
        ]);
    }
}
