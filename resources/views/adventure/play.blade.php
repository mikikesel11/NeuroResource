{{-- The Adventure. The accessible engine (resources/js/adventure.js) reads the
     story from the JSON island below and renders into #adventure. --}}
<x-public-layout title="The Adventure">
    <section class="mx-auto max-w-2xl px-4 py-16">
        <p class="text-sm uppercase tracking-wide text-[var(--ns-muted)]">The Adventure</p>
        <h1 class="mt-1 text-3xl font-semibold">{{ $storyTitle }}</h1>

        <div id="adventure" class="adventure mt-8" aria-label="{{ $storyTitle }}">
            {{-- Replaced by the engine once loaded; fallbacks below. --}}
            <p class="adventure-loading text-[var(--ns-muted)]">Loading the Adventure…</p>
            <noscript>
                <p class="text-[var(--ns-muted)]">
                    This Adventure needs JavaScript to play. The story is a gentle, branching
                    walk with no time pressure and no wrong choices.
                </p>
            </noscript>
        </div>
    </section>

    {{-- Story data island, read by the engine. --}}
    <script type="application/json" id="adventure-data">{!! $storyJson !!}</script>

    @push('scripts')
        @vite('resources/js/adventure.js')
    @endpush
</x-public-layout>
