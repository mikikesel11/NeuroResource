<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.public-layout', ['title' => 'Create account'])] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        // Show the temporary signed-in banner; go home, never the dashboard.
        session()->flash('justLoggedIn', true);

        $this->redirect(route('home', absolute: false));
    }
}; ?>

<section class="mx-auto max-w-md px-4 py-16">
    <h1 class="text-3xl font-semibold">Create an account</h1>
    <p class="mt-2 text-[var(--ns-muted)]">Join NeuroResource — it only takes a moment.</p>

    <form wire:submit="register" class="mt-8 rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-6 space-y-5">
        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium">Name</label>
            <input type="text" id="name" wire:model="name" required autofocus autocomplete="name"
                   class="mt-1 w-full rounded-md border border-[var(--ns-border)] bg-[var(--ns-bg)] px-3 py-2 text-[var(--ns-text)]">
            @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input type="email" id="email" wire:model="email" required autocomplete="username"
                   class="mt-1 w-full rounded-md border border-[var(--ns-border)] bg-[var(--ns-bg)] px-3 py-2 text-[var(--ns-text)]">
            @error('email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input type="password" id="password" wire:model="password" required autocomplete="new-password"
                   class="mt-1 w-full rounded-md border border-[var(--ns-border)] bg-[var(--ns-bg)] px-3 py-2 text-[var(--ns-text)]">
            @error('password')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Confirm Password</label>
            <input type="password" id="password_confirmation" wire:model="password_confirmation" required autocomplete="new-password"
                   class="mt-1 w-full rounded-md border border-[var(--ns-border)] bg-[var(--ns-bg)] px-3 py-2 text-[var(--ns-text)]">
            @error('password_confirmation')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <a href="{{ route('login') }}" wire:navigate class="text-sm text-[var(--ns-accent)] underline underline-offset-4">
                Already registered?
            </a>
            <button type="submit" class="rounded-md bg-[var(--ns-accent)] px-6 py-2.5 font-medium text-[var(--ns-accent-contrast)]">
                Register
            </button>
        </div>
    </form>
</section>
