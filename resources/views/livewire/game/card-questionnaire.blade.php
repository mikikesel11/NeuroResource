<div class="mx-auto max-w-lg px-4 py-12">
    <h1
        id="questionnaire-heading"
        tabindex="-1"
        class="mb-2 text-center text-2xl font-semibold text-[var(--ns-text)]"
    >
        Choose Your Deck
    </h1>
    <p class="mb-8 text-center text-[var(--ns-muted)]">What do you need right now?</p>

    <ul class="flex flex-col gap-4" aria-labelledby="questionnaire-heading" role="list">
        @foreach ($decks as $slug => $deck)
            <li>
                <button
                    wire:click="choose('{{ $slug }}')"
                    class="w-full rounded-xl border-2 bg-gradient-to-br px-6 py-4 text-left transition hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ns-focus)] {{ $deck['border'] }} {{ $deck['bg'] }}"
                >
                    <span class="block text-lg font-bold {{ $deck['accent'] }}">{{ $deck['title'] }}</span>
                    <span class="mt-0.5 block text-sm font-normal text-[var(--ns-text)]">{{ $deck['prompt'] }}</span>
                </button>
            </li>
        @endforeach
    </ul>
</div>
