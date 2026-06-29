<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.public-layout', ['title' => 'Confirm password'])] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('home', absolute: false), navigate: true);
    }
}; ?>

<section class="mx-auto max-w-md px-4 py-16">
    <h1 class="text-3xl font-semibold">Confirm your Password</h1>
    <p class="mt-2 text-[var(--ns-muted)]">
        This is a secure area. Please confirm your Password before continuing.
    </p>

    <form wire:submit="confirmPassword" class="mt-8 rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-6 space-y-5">
        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input type="password" id="password" wire:model="password" required autocomplete="current-password"
                   class="mt-1 w-full rounded-md border border-[var(--ns-border)] bg-[var(--ns-bg)] px-3 py-2 text-[var(--ns-text)]">
            @error('password')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end pt-1">
            <button type="submit" class="rounded-md bg-[var(--ns-accent)] px-6 py-2.5 font-medium text-[var(--ns-accent-contrast)]">
                Confirm
            </button>
        </div>
    </form>
</section>
