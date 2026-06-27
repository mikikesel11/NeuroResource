<div class="mx-auto max-w-5xl px-4 py-16">
    <header class="mb-8">
        <p class="text-sm uppercase tracking-wide text-[var(--ns-muted)]">Resource Library</p>
        <h1 class="mt-1 text-3xl font-semibold">Free Tools to Take and Share</h1>
        <p class="mt-3 max-w-2xl text-[var(--ns-muted)]">
            Practical Resources for Focus, Regulation, and Rest. Some are Free to
            download right away; others are Free in exchange for your Email.
        </p>
    </header>

    @if ($tags->isNotEmpty())
        <nav aria-label="Filter by Tag" class="mb-8">
            <ul class="flex flex-wrap gap-2">
                <li>
                    <button type="button" wire:click="$set('tag', null)"
                            @class([
                                'rounded-full border px-3 py-1 text-sm',
                                'border-[var(--ns-accent)] bg-[var(--ns-accent)] text-[var(--ns-accent-contrast)]' => ! $tag,
                                'border-[var(--ns-border)]' => (bool) $tag,
                            ])
                            @if (! $tag) aria-current="true" @endif>
                        All
                    </button>
                </li>
                @foreach ($tags as $t)
                    <li>
                        <button type="button" wire:click="$set('tag', '{{ $t->slug }}')"
                                @class([
                                    'rounded-full border px-3 py-1 text-sm',
                                    'border-[var(--ns-accent)] bg-[var(--ns-accent)] text-[var(--ns-accent-contrast)]' => $tag === $t->slug,
                                    'border-[var(--ns-border)]' => $tag !== $t->slug,
                                ])
                                @if ($tag === $t->slug) aria-current="true" @endif>
                            {{ $t->name }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if ($resources->isEmpty())
        <p class="text-[var(--ns-muted)]">No Resources match this Filter yet.</p>
    @else
        <ul class="grid gap-5 sm:grid-cols-2">
            @foreach ($resources as $resource)
                <li class="flex h-full flex-col rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-semibold">
                            <a href="{{ route('resources.show', $resource->slug) }}" wire:navigate
                               class="hover:text-[var(--ns-accent)] focus-visible:text-[var(--ns-accent)]">
                                {{ $resource->title }}
                            </a>
                        </h2>
                        <x-resources.access-badge :resource="$resource" />
                    </div>
                    <p class="mt-2 flex-1 text-[var(--ns-muted)]">{{ $resource->summary }}</p>
                    <p class="mt-4">
                        <a href="{{ route('resources.show', $resource->slug) }}" wire:navigate
                           class="text-[var(--ns-accent)] underline underline-offset-4">
                            View Resource
                        </a>
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</div>
