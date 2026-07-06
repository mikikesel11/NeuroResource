<div class="mx-auto max-w-3xl px-4 py-12">
    <p class="mb-6">
        <a href="{{ route('blog') }}" wire:navigate
           class="text-sm text-[var(--ns-accent)] underline underline-offset-4">← Back to the Blog</a>
    </p>

    <article>
        <header>
            <h1 class="text-3xl font-semibold leading-tight">{{ $post->title }}</h1>
            <x-blog.post-meta :post="$post" class="mt-3" />

            @if ($post->tags->isNotEmpty())
                <ul class="mt-3 flex flex-wrap gap-2" aria-label="Tags">
                    @foreach ($post->tags as $t)
                        <li>
                            <a href="{{ route('blog', ['tag' => $t->slug]) }}" wire:navigate
                               class="rounded-full border border-[var(--ns-border)] px-2 py-0.5 text-xs text-[var(--ns-muted)] hover:border-[var(--ns-accent)]">
                                {{ $t->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </header>

        @if ($post->excerpt)
            <p class="mt-6 text-lg text-[var(--ns-muted)]">{{ $post->excerpt }}</p>
        @endif

        {{-- Accessible reading view: comfortable measure, generous spacing. --}}
        <div class="ns-prose mt-8 space-y-4 text-[var(--ns-text)]">
            {!! safe_markdown($post->body) !!}
        </div>
    </article>
</div>
