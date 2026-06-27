<div class="mx-auto max-w-3xl px-4 py-16">
    <header class="mb-8">
        <p class="text-sm uppercase tracking-wide text-[var(--ns-muted)]">The Blog</p>
        <h1 class="mt-1 text-3xl font-semibold">Plain-Language Writing, at Your Pace</h1>
        <p class="mt-3 text-[var(--ns-muted)]">
            Honest, jargon-light writing on Focus, Regulation, Sensory Load, and
            living as a NeuroDivergent person.
            <a href="{{ route('feeds.blog') }}" class="text-[var(--ns-accent)] underline underline-offset-4">
                Subscribe via RSS
            </a>.
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

    @if ($posts->isEmpty())
        <p class="text-[var(--ns-muted)]">No Posts match this Filter yet.</p>
    @else
        <ul class="space-y-8">
            @foreach ($posts as $post)
                <li>
                    <article>
                        <h2 class="text-xl font-semibold">
                            <a href="{{ route('blog.show', $post->slug) }}" wire:navigate
                               class="hover:text-[var(--ns-accent)] focus-visible:text-[var(--ns-accent)]">
                                {{ $post->title }}
                            </a>
                        </h2>
                        <x-blog.post-meta :post="$post" class="mt-1" />
                        <p class="mt-2 text-[var(--ns-muted)]">{{ $post->excerpt }}</p>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif
</div>
