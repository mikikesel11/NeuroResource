{{-- Shared placeholder for nav destinations not yet built, so links never 404. --}}
<x-public-layout :title="$heading">
    <section class="mx-auto max-w-5xl px-4 py-24 text-center">
        <p class="text-sm uppercase tracking-wide text-[var(--ns-muted)]">Coming Soon</p>
        <h1 class="mt-3 text-3xl font-semibold">{{ $heading }}</h1>
        <p class="mx-auto mt-4 max-w-xl text-lg text-[var(--ns-muted)]">
            This section is being built with Care. Check back soon — or head back to the
            <a href="{{ route('home') }}" wire:navigate class="text-[var(--ns-accent)] underline underline-offset-4">Home page</a>.
        </p>
    </section>
</x-public-layout>
