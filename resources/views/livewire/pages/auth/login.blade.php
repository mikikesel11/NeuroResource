<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.public-layout', ['title' => 'Log in'])] class extends Component
{
    public LoginForm $form;

    // The page the visitor was on when they chose "Log in" (set by the link).
    #[Url]
    public ?string $redirect = null;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        // One-time signal for the temporary "signed in" banner on the next page.
        session()->flash('justLoggedIn', true);

        // Prefer the originating page; otherwise honor an intended URL (e.g. a
        // guarded route the guest tried to reach); otherwise go home — never the
        // dashboard. Full redirect (no SPA navigate) so the flash banner shows.
        if ($target = $this->safeRedirect()) {
            $this->redirect($target);

            return;
        }

        $this->redirectIntended(default: route('home', absolute: false));
    }

    /** Only allow a local, non-auth path to prevent open-redirects and loops. */
    private function safeRedirect(): ?string
    {
        $to = $this->redirect;

        if (! $to || ! str_starts_with($to, '/') || str_starts_with($to, '//')) {
            return null;
        }

        foreach (['/login', '/register', '/forgot-password', '/reset-password'] as $authPath) {
            if (str_starts_with($to, $authPath)) {
                return null;
            }
        }

        return $to;
    }
}; ?>

<section class="mx-auto max-w-md px-4 py-16">
    <h1 class="text-3xl font-semibold">Log in</h1>
    <p class="mt-2 text-[var(--ns-muted)]">Welcome back. Pick up right where you left off.</p>

    {{-- Session status (e.g. after a password reset) --}}
    @if (session('status'))
        <p role="status" class="mt-4 rounded-md bg-[var(--ns-surface)] px-4 py-3 text-sm text-[var(--ns-accent)]">
            {{ session('status') }}
        </p>
    @endif

    <form wire:submit="login" class="mt-8 rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-6 space-y-5">
        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input type="email" id="email" wire:model="form.email" required autofocus autocomplete="username"
                   class="mt-1 w-full rounded-md border border-[var(--ns-border)] bg-[var(--ns-bg)] px-3 py-2 text-[var(--ns-text)]">
            @error('form.email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input type="password" id="password" wire:model="form.password" required autocomplete="current-password"
                   class="mt-1 w-full rounded-md border border-[var(--ns-border)] bg-[var(--ns-bg)] px-3 py-2 text-[var(--ns-text)]">
            @error('form.password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember me --}}
        <label for="remember" class="flex items-center gap-2 text-sm text-[var(--ns-muted)]">
            <input type="checkbox" id="remember" wire:model="form.remember"
                   class="rounded border-[var(--ns-border)]">
            Remember me
        </label>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate
                   class="text-sm text-[var(--ns-accent)] underline underline-offset-4">
                    Forgot your password?
                </a>
            @endif

            <button type="submit"
                    class="rounded-md bg-[var(--ns-accent)] px-6 py-2.5 font-medium text-[var(--ns-accent-contrast)]">
                Log in
            </button>
        </div>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-sm text-[var(--ns-muted)]">
            New here?
            <a href="{{ route('register') }}" wire:navigate class="text-[var(--ns-accent)] underline underline-offset-4">
                Create an account
            </a>.
        </p>
    @endif
</section>
