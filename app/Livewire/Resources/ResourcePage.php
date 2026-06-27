<?php

namespace App\Livewire\Resources;

use App\Domains\Resources\Mail\ConfirmResourceUnlock;
use App\Domains\Resources\Models\Resource;
use App\Domains\Resources\Models\ResourceUnlock;
use App\Domains\Resources\Support\ResourceGate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.public-layout', ['title' => 'Resource'])]
class ResourcePage extends Component
{
    public string $slug;

    public ?string $email = null;

    public bool $pendingConfirmation = false;

    public function mount(string $slug): void
    {
        abort_unless(Resource::published()->where('slug', $slug)->exists(), 404);

        $this->slug = $slug;
    }

    /**
     * Email-gate capture (double opt-in): record a pending unlock and email a
     * confirmation link. Access is granted only after the recipient confirms.
     */
    public function unlock(): void
    {
        $data = $this->validate(['email' => ['required', 'email', 'max:255']]);

        $resource = $this->resource();

        $unlock = ResourceUnlock::create([
            'resource_id' => $resource->id,
            'user_id' => auth()->id(),
            'email' => $data['email'],
            'token' => Str::random(40),
        ]);

        Mail::to($data['email'])->send(new ConfirmResourceUnlock($unlock));

        $this->pendingConfirmation = true;
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
            'justConfirmed' => (bool) session('justConfirmed'),
        ]);
    }
}
