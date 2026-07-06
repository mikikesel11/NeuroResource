<?php

declare(strict_types=1);

namespace App\Livewire\Resources;

use App\Domains\Resources\Mail\ConfirmResourceUnlock;
use App\Domains\Resources\Models\Resource;
use App\Domains\Resources\Models\ResourceUnlock;
use App\Domains\Resources\Support\ResourceGate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
     *
     * The endpoint is public + unauthenticated, so it is rate limited per
     * IP + email to prevent driving unbounded confirmation mail to arbitrary
     * addresses (mail-bombing / provider-cost abuse).
     *
     * @throws ValidationException
     */
    public function unlock(): void
    {
        $data = $this->validate(['email' => ['required', 'email', 'max:255']]);

        $this->ensureIsNotRateLimited($data['email']);

        $resource = $this->resource();

        $unlock = ResourceUnlock::create([
            'resource_id' => $resource->id,
            'user_id' => auth()->id(),
            'email' => $data['email'],
        ]);
        $unlock->token = Str::random(40);
        $unlock->save();

        RateLimiter::hit($this->throttleKey($data['email']), $this->decaySeconds());

        Mail::to($data['email'])->send(new ConfirmResourceUnlock($unlock));

        $this->pendingConfirmation = true;
    }

    /**
     * Reject the request with a friendly, accessible notice when the caller has
     * exceeded the per-IP + email attempt limit — never send in that case.
     *
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(string $email): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($email), $this->maxAttempts())) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($email));

        throw ValidationException::withMessages([
            'email' => trans('messages.resources.unlock_throttled', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(string $email): string
    {
        return 'resource-unlock|'.Str::transliterate(Str::lower($email)).'|'.request()->ip();
    }

    protected function maxAttempts(): int
    {
        return (int) config('neuroresource.unlock_max_attempts', 5);
    }

    protected function decaySeconds(): int
    {
        return (int) config('neuroresource.unlock_decay_seconds', 60);
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
