<?php

namespace App\Livewire\Resources;

use App\Domains\Resources\Models\Resource;
use App\Domains\Resources\Models\ResourceUnlock;
use App\Domains\Resources\Support\ResourceGate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.public-layout', ['title' => 'Resource'])]
class ResourcePage extends Component
{
    public string $slug;

    public ?string $email = null;

    public bool $justUnlocked = false;

    public function mount(string $slug): void
    {
        abort_unless(Resource::published()->where('slug', $slug)->exists(), 404);

        $this->slug = $slug;
    }

    /** Email-gate capture: record the email, then unlock for this session. */
    public function unlock(): void
    {
        $data = $this->validate(['email' => ['required', 'email', 'max:255']]);

        $resource = $this->resource();

        ResourceUnlock::create([
            'resource_id' => $resource->id,
            'user_id' => auth()->id(),
            'email' => $data['email'],
        ]);

        session()->push(ResourceGate::SESSION_KEY, $resource->slug);

        $this->justUnlocked = true;
        // Next increment: trigger double opt-in confirmation + mailing-list sync.
    }

    protected function resource(): Resource
    {
        return Resource::published()->with('tags')->where('slug', $this->slug)->firstOrFail();
    }

    public function render()
    {
        $resource = $this->resource();

        return view('livewire.resources.resource-page', [
            'resource' => $resource,
            'unlocked' => ResourceGate::unlocked($resource),
        ]);
    }
}
