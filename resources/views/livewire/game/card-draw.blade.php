<div
    x-data="{
        phase: @entangle('phase'),
        reduceMotion: document.documentElement.dataset.reduceMotion === 'true',

        startDraw() {
            if (this.reduceMotion) {
                return; // wire:click handles draw(); phase set by server to 'revealed'
            }
            this.phase = 'shuffling';
        },

        onCardDrawn() {
            if (this.reduceMotion || this.phase === 'revealed') return;
            this.phase = 'flipping';
        },

        onFlipEnd(event) {
            if (this.phase === 'flipping' && event.propertyName === 'transform') {
                this.phase = 'revealed';
            }
        }
    }"
    @card-drawn.window="onCardDrawn()"
    class="mx-auto max-w-lg px-4 py-12"
>
    {{-- Accessible live region announces card name when revealed --}}
    <div aria-live="polite" aria-atomic="true" class="sr-only">
        @if ($result && $phase === 'revealed')
            {{ $result['card']->name }}
        @endif
    </div>

    <h1 class="mb-8 text-center text-2xl font-semibold text-[var(--ns-text)]">
        {{ ucfirst($deck) }} Deck
    </h1>

    {{-- ── IDLE STATE ─────────────────────────────────────────────────── --}}
    <div x-show="phase === 'idle'" x-cloak class="flex flex-col items-center gap-6">
        <p class="text-center text-[var(--ns-muted)]">
            Draw a card when you're ready.
        </p>

        <button
            x-on:click="startDraw()"
            wire:click="draw"
            wire:loading.attr="disabled"
            class="rounded-lg bg-[var(--ns-accent)] px-8 py-3 font-semibold text-[var(--ns-accent-contrast)] transition hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ns-focus)] disabled:opacity-60"
        >
            Draw a Card
        </button>
    </div>

    {{-- ── SHUFFLING STATE ─────────────────────────────────────────────── --}}
    <div x-show="phase === 'shuffling'" x-cloak class="flex flex-col items-center gap-8">
        <p class="text-center text-[var(--ns-muted)]">Shuffling…</p>

        <div class="relative h-52 w-36">
            @foreach (['-translate-x-4 -rotate-6', 'translate-x-0 rotate-0', 'translate-x-4 rotate-6'] as $offset)
                <div
                    class="card-shuffling absolute inset-0 overflow-hidden rounded-xl border-2 border-[var(--ns-border)] shadow-md {{ $offset }}"
                    style="{{ $deckTheme['back_image'] ? 'background-image: url(\''.asset($deckTheme['back_image']).'\'); background-size: cover; background-position: center;' : 'background-color: var(--ns-surface);' }}"
                    role="presentation"
                ></div>
            @endforeach
        </div>
    </div>

    {{-- ── FLIPPING + REVEALED STATES ──────────────────────────────────── --}}
    <div x-show="phase === 'flipping' || phase === 'revealed'" x-cloak class="flex flex-col items-center gap-8">

        <div class="card-scene h-80 w-56">
            <div
                class="card-inner h-full w-full rounded-xl"
                :class="{ 'is-flipped': phase === 'revealed' }"
                @transitionend="onFlipEnd($event)"
            >
                {{-- Card back --}}
                <div
                    class="card-back overflow-hidden rounded-xl border-2 border-[var(--ns-border)] shadow-lg"
                    style="{{ $deckTheme['back_image'] ? 'background-image: url(\''.asset($deckTheme['back_image']).'\'); background-size: cover; background-position: center;' : 'background-color: var(--ns-surface);' }}"
                    role="presentation"
                ></div>

                {{-- Card front --}}
                <div class="card-face overflow-y-auto rounded-xl border-2 bg-gradient-to-br p-5 shadow-lg {{ $deckTheme['border'] }} {{ $deckTheme['bg'] }}">
                    @if ($result)
                        <h2
                            id="card-title"
                            tabindex="-1"
                            class="text-lg font-bold leading-tight {{ $deckTheme['accent'] }}"
                        >
                            {{ $result['card']->name }}
                        </h2>

                        <p class="mt-3 text-sm leading-relaxed text-[var(--ns-text)]">
                            {{ $result['card']->description }}
                        </p>

                        @if (!empty($result['card']->subtasks))
                            <ul class="mt-4 space-y-2" aria-label="Subtasks">
                                @foreach ($result['card']->subtasks as $i => $subtask)
                                    <li>
                                        <label class="flex cursor-pointer items-start gap-2 text-sm text-[var(--ns-text)]">
                                            <input
                                                type="checkbox"
                                                wire:click="toggleSubtask({{ $i }})"
                                                @checked(in_array($i, $checkedSubtasks))
                                                class="mt-0.5 size-4 rounded border-[var(--ns-border)] text-[var(--ns-accent)] focus:ring-[var(--ns-focus)]"
                                            >
                                            <span @class(['line-through opacity-60' => in_array($i, $checkedSubtasks)])>
                                                {{ $subtask }}
                                            </span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-4 flex items-center gap-1.5">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $deckTheme['accent'] }} bg-white/50">
                                +{{ $result['xp_awarded'] }} XP
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Focus management: move to card title after flip completes --}}
        <div
            x-show="phase === 'revealed'"
            x-init="$watch('phase', v => { if (v === 'revealed') $nextTick(() => document.getElementById('card-title')?.focus()) })"
        ></div>

        <button
            x-show="phase === 'revealed'"
            wire:click="resetDraw"
            class="rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] px-6 py-2.5 text-sm font-medium text-[var(--ns-text)] transition hover:bg-[var(--ns-bg)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ns-focus)]"
        >
            Draw Another Card
        </button>
    </div>
</div>
