<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.public-layout', ['title' => 'Verify email'])] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('home', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mx-auto max-w-md px-4 py-16">
    <h1 class="text-3xl font-semibold">Verify your Email</h1>
    <p class="mt-2 text-[var(--ns-muted)]">
        Thanks for signing up! Please confirm your Email by selecting the link we just
        sent you. If it didn't arrive, we'll gladly send another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <p role="status" class="mt-4 rounded-md bg-[var(--ns-surface)] px-4 py-3 text-sm text-[var(--ns-accent)]">
            A new verification link has been sent to your Email address.
        </p>
    @endif

    <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
        <button type="button" wire:click="sendVerification"
                class="rounded-md bg-[var(--ns-accent)] px-6 py-2.5 font-medium text-[var(--ns-accent-contrast)]">
            Resend Verification Email
        </button>

        <button type="button" wire:click="logout"
                class="text-sm text-[var(--ns-muted)] underline underline-offset-4 hover:text-[var(--ns-text)]">
            Log out
        </button>
    </div>
</section>
