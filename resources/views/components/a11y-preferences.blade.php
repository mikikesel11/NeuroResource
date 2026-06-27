@php
    // Display & Accessibility controls. Native <details> disclosure = keyboard
    // accessible by default. Buttons drive window.nsPrefs (resources/js/preferences.js),
    // which persists to localStorage, applies live, AND reflects the current
    // selection back here (aria-pressed + a CSS checkmark). See design §3.5.
    $themes = [
        'light' => 'Light',
        'dark' => 'Dark',
        'high-contrast' => 'High Contrast',
        'low-stimulation' => 'Low Stimulation',
    ];
@endphp

<details class="relative">
    <summary class="cursor-pointer list-none rounded border border-[var(--ns-border)] px-3 py-1.5 text-sm select-none">
        ⚙︎ {{ __('messages.a11y.preferences') }}
    </summary>

    <div role="group" aria-label="{{ __('messages.a11y.preferences') }}"
         class="absolute right-0 z-50 mt-2 w-72 max-w-[calc(100vw-1.5rem)] rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-4 shadow-lg">

        <fieldset class="mb-4">
            <legend class="mb-2 text-sm font-medium">{{ __('messages.a11y.theme') }}</legend>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($themes as $value => $label)
                    <button type="button" data-ns-theme="{{ $value }}" aria-pressed="false"
                            onclick="nsPrefs.setTheme('{{ $value }}')"
                            class="ns-pref-option rounded border border-[var(--ns-border)] px-2 py-1.5 text-sm text-left">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <button type="button" data-ns-action="follow-system" aria-pressed="false"
                    onclick="nsPrefs.setTheme(null)"
                    class="ns-pref-option mt-2 rounded px-1 text-xs text-[var(--ns-muted)] underline underline-offset-2">
                Follow System theme (currently <span data-ns-effective-theme>Light</span>)
            </button>
        </fieldset>

        <fieldset class="mb-4">
            <legend class="mb-2 text-sm font-medium">{{ __('messages.a11y.text_size') }}</legend>
            <div class="flex items-center gap-2">
                <button type="button" onclick="nsPrefs.adjustText(-0.1)" aria-label="Decrease text size"
                        class="rounded border border-[var(--ns-border)] px-3 py-1.5 text-sm">A−</button>
                <output data-ns-text-display aria-live="polite"
                        class="min-w-[3.5rem] text-center text-sm tabular-nums">100%</output>
                <button type="button" onclick="nsPrefs.adjustText(0.1)" aria-label="Increase text size"
                        class="rounded border border-[var(--ns-border)] px-3 py-1.5 text-base">A+</button>
            </div>
        </fieldset>

        <div class="flex items-center justify-between gap-2">
            <button type="button" data-ns-motion aria-pressed="false"
                    onclick="nsPrefs.toggleMotion()"
                    class="ns-pref-option rounded border border-[var(--ns-border)] px-3 py-1.5 text-sm">
                {{ __('messages.a11y.reduce_motion') }}
            </button>
            <button type="button" onclick="nsPrefs.reset()"
                    class="text-xs text-[var(--ns-muted)] underline underline-offset-2">
                Reset
            </button>
        </div>
    </div>
</details>
