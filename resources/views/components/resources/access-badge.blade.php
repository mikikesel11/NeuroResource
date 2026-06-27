@props(['resource'])

@if ($resource->isEmailGated())
    <span class="shrink-0 rounded-full border border-[var(--ns-accent)] px-2 py-0.5 text-xs text-[var(--ns-accent)]">
        {{ __('messages.resources.email_label') }}
    </span>
@else
    <span class="shrink-0 rounded-full bg-[var(--ns-accent)] px-2 py-0.5 text-xs text-[var(--ns-accent-contrast)]">
        {{ __('messages.resources.free_label') }}
    </span>
@endif
