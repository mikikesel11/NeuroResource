<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.public-layout', ['title' => 'Forgot password'])] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<section class="mx-auto max-w-md px-4 py-16">
    <h1 class="text-3xl font-semibold">Forgot your password?</h1>
    <p class="mt-2 text-[var(--ns-muted)]">
        No problem. Tell us your Email and we'll send a link to choose a new Password.
    </p>

    @if (session('status'))
        <p role="status" class="mt-4 rounded-md bg-[var(--ns-surface)] px-4 py-3 text-sm text-[var(--ns-accent)]">
            {{ session('status') }}
        </p>
    @endif

    <form wire:submit="sendPasswordResetLink" class="mt-8 rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-6 space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input type="email" id="email" wire:model="email" required autofocus autocomplete="username"
                   class="mt-1 w-full rounded-md border border-[var(--ns-border)] bg-[var(--ns-bg)] px-3 py-2 text-[var(--ns-text)]">
            @error('email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <a href="{{ route('login') }}" wire:navigate class="text-sm text-[var(--ns-accent)] underline underline-offset-4">
                Back to Log in
            </a>
            <button type="submit" class="rounded-md bg-[var(--ns-accent)] px-6 py-2.5 font-medium text-[var(--ns-accent-contrast)]">
                Email Password Reset Link
            </button>
        </div>
    </form>
</section>
