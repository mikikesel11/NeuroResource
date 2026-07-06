<div
    x-data="{
        phase: @entangle('phase'),
        cardDone: @entangle('cardDone'),
        reduceMotion: document.documentElement.dataset.reduceMotion === 'true',

        /* Opt-in, self-started timer. In-memory only — resets on reload/navigation.
           No sound, no autoplay: a gentle reminder, never imposed time pressure. */
        remaining: 0,
        timerActive: false,
        reminder: false,
        timerId: null,
        announce: '',

        startTimer(minutes) {
            this.cancelTimer();
            this.remaining = minutes * 60;
            this.timerActive = true;
            this.announce = 'Timer started for ' + minutes + (minutes === 1 ? ' minute.' : ' minutes.');
            this.timerId = setInterval(() => this.tick(), 1000);
        },

        tick() {
            this.remaining--;
            if (this.remaining <= 0) {
                clearInterval(this.timerId);
                this.timerId = null;
                this.timerActive = false;
                if (!this.cardDone) {
                    this.reminder = true;
                    this.announce = 'Time is up — here is your reminder.';
                }
            }
        },

        cancelTimer() {
            if (this.timerId) clearInterval(this.timerId);
            this.timerId = null;
            this.timerActive = false;
            this.reminder = false;
        },

        mmss() {
            const s = Math.max(0, this.remaining);
            const m = Math.floor(s / 60);
            const sec = String(s % 60).padStart(2, '0');
            return m + ':' + sec;
        },

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
        },

        init() {
            // Completing the card (button or last subtask) cancels the timer.
            this.$watch('cardDone', v => { if (v) this.cancelTimer(); });
            // Drawing another card clears any running timer.
            this.$watch('phase', v => { if (v === 'idle') this.cancelTimer(); });
            // Move focus to the reminder when it appears.
            this.$watch('reminder', v => { if (v) this.$nextTick(() => this.$refs.reminderPanel?.focus()); });
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

    {{-- Polite live region for timer start / reminder (never per-second) --}}
    <p class="sr-only" aria-live="polite" x-text="announce"></p>

    {{-- ── TIMER HUD ───────────────────────────────────────────────────── --}}
    <div x-show="timerActive" x-cloak class="mb-6 flex justify-center">
        <span class="ns-card-timer">
            ⏳ <span x-text="mmss()" aria-hidden="true"></span> left
        </span>
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

                        {{-- XP is earned on completion, not on draw. --}}
                        <div class="mt-4 flex items-center gap-1.5">
                            <span x-show="!cardDone" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $deckTheme['accent'] }} bg-white/50">
                                Earn +{{ $result['card']->xp_earned }} XP
                            </span>
                            <span x-show="cardDone" x-cloak class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $deckTheme['accent'] }} bg-white/50">
                                +{{ $result['xp_awarded'] }} XP earned ✓
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

        {{-- ── CARD ACTIONS (revealed) ─────────────────────────────────── --}}
        <div x-show="phase === 'revealed'" x-cloak class="flex flex-col items-center gap-3">
            @if ($result && $result['card']->timer_minutes)
                <button
                    x-show="!timerActive && !cardDone"
                    x-on:click="startTimer({{ (int) $result['card']->timer_minutes }})"
                    class="rounded-lg bg-[var(--ns-accent)] px-6 py-2.5 text-sm font-semibold text-[var(--ns-accent-contrast)] transition hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ns-focus)]"
                >
                    Start a {{ (int) $result['card']->timer_minutes }}-minute Timer
                </button>
            @endif

            <button
                x-show="!cardDone"
                wire:click="markDone"
                class="rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] px-6 py-2.5 text-sm font-medium text-[var(--ns-text)] transition hover:bg-[var(--ns-bg)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ns-focus)]"
            >
                Mark this Card Done
            </button>

            <span x-show="cardDone" x-cloak class="text-sm font-medium text-[var(--ns-accent)]">
                Done ✓
            </span>

            <button
                wire:click="resetDraw"
                class="rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] px-6 py-2.5 text-sm font-medium text-[var(--ns-text)] transition hover:bg-[var(--ns-bg)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ns-focus)]"
            >
                Draw Another Card
            </button>
        </div>
    </div>

    {{-- ── TIMER REMINDER ──────────────────────────────────────────────── --}}
    <div
        x-show="reminder"
        x-cloak
        x-ref="reminderPanel"
        role="status"
        tabindex="-1"
        class="ns-card-reminder mt-8"
    >
        @if ($result)
            <p class="text-sm font-semibold text-[var(--ns-text)]">
                ⏰ Time for: {{ $result['card']->name }}
            </p>
            <p class="mt-1 text-sm text-[var(--ns-text)]">
                {{ $result['card']->description }}
            </p>
        @endif

        <div class="mt-3 flex flex-wrap gap-3">
            <button
                wire:click="markDone"
                class="rounded-lg bg-[var(--ns-accent)] px-4 py-2 text-sm font-semibold text-[var(--ns-accent-contrast)] transition hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ns-focus)]"
            >
                Mark this Card Done
            </button>
            <button
                x-on:click="reminder = false"
                class="rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] px-4 py-2 text-sm font-medium text-[var(--ns-text)] transition hover:bg-[var(--ns-bg)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ns-focus)]"
            >
                Dismiss
            </button>
        </div>
    </div>
</div>
