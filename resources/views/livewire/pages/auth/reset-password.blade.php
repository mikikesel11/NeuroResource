<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('components.public-layout', ['title' => 'Reset password'])] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<section class="mx-auto max-w-md px-4 py-16">
    <h1 class="text-3xl font-semibold">Choose a new Password</h1>
    <p class="mt-2 text-[var(--ns-muted)]">Almost there — set a new Password for your account.</p>

    <form wire:submit="resetPassword" class="mt-8 rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-6 space-y-5">
        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input type="email" id="email" wire:model="email" required autofocus autocomplete="username"
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

        <div class="flex justify-end pt-1">
            <button type="submit" class="rounded-md bg-[var(--ns-accent)] px-6 py-2.5 font-medium text-[var(--ns-accent-contrast)]">
                Reset Password
            </button>
        </div>
    </form>
</section>
