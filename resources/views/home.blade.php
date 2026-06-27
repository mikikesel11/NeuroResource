@php
    // Home page. Prose follows the "Capitalize Key Terms" style — docs/writing-style.md.
    $cards = [
        ['route' => 'shop',      'title' => 'The Shop',          'body' => 'Tools, Printables, and Goods chosen to support Focus, Regulation, and Rest.'],
        ['route' => 'blog',      'title' => 'The Blog',          'body' => 'Plain-language writing on Executive Function, Sensory Load, Masking, and Burnout.'],
        ['route' => 'resources', 'title' => 'Resource Library',  'body' => 'Free and Email-gated Resources you can download, print, and share.'],
        ['route' => 'about',     'title' => 'About',             'body' => 'Meet the person behind NeuroScouts — Biography, Certifications, and Credentials.'],
        ['route' => 'play',      'title' => 'The Adventure',     'body' => 'A gentle, click-through Adventure you move through at your own pace.'],
    ];
@endphp

<x-public-layout title="Home">
    <section class="mx-auto max-w-5xl px-4 pt-16 pb-12">
        <p class="text-sm uppercase tracking-wide text-[var(--ns-muted)]">Welcome to NeuroScouts</p>
        <h1 class="mt-3 text-3xl sm:text-4xl font-semibold leading-tight max-w-3xl">
            {{ __('messages.home.tagline') }}
        </h1>
        <p class="mt-5 max-w-2xl text-lg text-[var(--ns-muted)]">
            This is a Calm, Predictable place. Take What helps, leave What doesn't, and
            adjust the Display to suit You — change the Theme, Text Size, and Motion any
            time from the
            <span class="font-medium text-[var(--ns-text)]">Display &amp; Accessibility</span>
            menu at the top.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('resources') }}" wire:navigate
               class="rounded-md bg-[var(--ns-accent)] px-5 py-2.5 font-medium text-[var(--ns-accent-contrast)]">
                Browse Free Resources
            </a>
            <a href="{{ route('shop') }}" wire:navigate
               class="rounded-md border border-[var(--ns-border)] px-5 py-2.5 font-medium">
                Visit the Shop
            </a>
        </div>
    </section>

    <section aria-labelledby="explore-heading" class="mx-auto max-w-5xl px-4 pb-8">
        <h2 id="explore-heading" class="text-xl font-semibold">What You'll Find Here</h2>
        <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $card)
                <li>
                    <a href="{{ route($card['route']) }}" wire:navigate
                       class="block h-full rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-5 hover:border-[var(--ns-accent)] focus-visible:border-[var(--ns-accent)]">
                        <h3 class="font-semibold text-[var(--ns-accent)]">{{ $card['title'] }}</h3>
                        <p class="mt-2 text-[var(--ns-muted)]">{{ $card['body'] }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
</x-public-layout>
