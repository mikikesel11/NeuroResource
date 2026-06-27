<div class="mx-auto max-w-3xl px-4 py-12">
    <p class="mb-6">
        <a href="{{ route('resources') }}" wire:navigate
           class="text-sm text-[var(--ns-accent)] underline underline-offset-4">← Back to the Resource Library</a>
    </p>

    <div class="flex items-start justify-between gap-3">
        <h1 class="text-3xl font-semibold">{{ $resource->title }}</h1>
        <x-resources.access-badge :resource="$resource" />
    </div>

    @if ($resource->tags->isNotEmpty())
        <ul class="mt-3 flex flex-wrap gap-2" aria-label="Tags">
            @foreach ($resource->tags as $t)
                <li class="rounded-full border border-[var(--ns-border)] px-2 py-0.5 text-xs text-[var(--ns-muted)]">{{ $t->name }}</li>
            @endforeach
        </ul>
    @endif

    <p class="mt-5 text-lg text-[var(--ns-muted)]">{{ $resource->summary }}</p>

    <div class="mt-8 rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-6">
        @if ($unlocked)
            @if ($justUnlocked)
                <p role="status" class="mb-4 text-sm text-[var(--ns-accent)]">
                    Thank you — your Resource is unlocked below.
                </p>
            @endif
            <a href="{{ route('resources.download', $resource->slug) }}"
               class="inline-block rounded-md bg-[var(--ns-accent)] px-6 py-3 font-medium text-[var(--ns-accent-contrast)]">
                {{ $resource->type === 'link' ? 'Open Resource' : 'Download Resource' }}
            </a>
        @else
            {{-- Email gate: capture an Email, then unlock for this session. --}}
            <h2 class="font-semibold">{{ __('messages.resources.unlock_cta') }}</h2>
            <p class="mt-1 text-sm text-[var(--ns-muted)]">
                We'll send occasional, useful Resources. No spam — leave any time.
            </p>
            <form wire:submit="unlock" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <label for="unlock-email" class="sr-only">Email address</label>
                    <input type="email" id="unlock-email" wire:model="email" autocomplete="email"
                           class="w-full rounded-md border border-[var(--ns-border)] bg-[var(--ns-bg)] px-3 py-2"
                           placeholder="you@example.com" required>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="rounded-md bg-[var(--ns-accent)] px-6 py-2 font-medium text-[var(--ns-accent-contrast)]">
                    Unlock
                </button>
            </form>
        @endif
    </div>
</div>
