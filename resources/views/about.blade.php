{{-- About the featured person: Biography + Certifications. Prose in the
     Capitalize Key Terms style — docs/writing-style.md. --}}
<x-public-layout title="About">
    @if ($profile)
        <article class="mx-auto max-w-3xl px-4 py-16">
            <header class="flex flex-col gap-5 sm:flex-row sm:items-center">
                @if ($profile->avatar_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($profile->avatar_path) }}"
                         alt="Portrait of {{ $profile->name }}"
                         class="h-28 w-28 rounded-full object-cover border border-[var(--ns-border)]">
                @endif
                <div>
                    <p class="text-sm uppercase tracking-wide text-[var(--ns-muted)]">About</p>
                    <h1 class="mt-1 text-3xl font-semibold">{{ $profile->name }}</h1>
                    @if ($profile->headline)
                        <p class="mt-1 text-lg text-[var(--ns-accent)]">{{ $profile->headline }}</p>
                    @endif
                </div>
            </header>

            @if ($profile->bio)
                <section aria-labelledby="bio-heading" class="mt-10">
                    <h2 id="bio-heading" class="text-xl font-semibold">Biography</h2>
                    <div class="ns-prose mt-4 space-y-4 text-[var(--ns-text)]">
                        {!! \Illuminate\Support\Str::markdown($profile->bio, [
                            'html_input' => 'strip',
                            'allow_unsafe_links' => false,
                        ]) !!}
                    </div>
                </section>
            @endif

            <section aria-labelledby="certs-heading" class="mt-12">
                <h2 id="certs-heading" class="text-xl font-semibold">Certifications &amp; Credentials</h2>

                @if ($profile->certifications->isNotEmpty())
                    <ul class="mt-4 space-y-4">
                        @foreach ($profile->certifications as $cert)
                            <li class="rounded-lg border border-[var(--ns-border)] bg-[var(--ns-surface)] p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold">{{ $cert->name }}</h3>
                                        <p class="text-[var(--ns-muted)]">{{ $cert->issuer }}</p>
                                    </div>
                                    @if ($cert->badge_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($cert->badge_path) }}"
                                             alt="{{ $cert->name }} badge" class="h-12 w-12 object-contain">
                                    @endif
                                </div>

                                @if ($cert->issued_on || $cert->expires_on)
                                    <p class="mt-2 text-sm text-[var(--ns-muted)]">
                                        @if ($cert->issued_on)
                                            <span>Issued {{ $cert->issued_on->format('F Y') }}</span>
                                        @endif
                                        @if ($cert->expires_on)
                                            <span> · Valid through {{ $cert->expires_on->format('F Y') }}</span>
                                        @endif
                                    </p>
                                @endif

                                @if ($cert->credential_url)
                                    <p class="mt-3">
                                        <a href="{{ $cert->credential_url }}" target="_blank" rel="noopener noreferrer"
                                           class="text-[var(--ns-accent)] underline underline-offset-4">
                                            Verify the {{ $cert->issuer }} {{ $cert->name }} credential
                                            <span class="sr-only">(opens in a new tab)</span>
                                        </a>
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-[var(--ns-muted)]">Certifications will be listed here soon.</p>
                @endif
            </section>
        </article>
    @else
        {{-- Graceful empty state before a Profile has been created. --}}
        <section class="mx-auto max-w-3xl px-4 py-24 text-center">
            <p class="text-sm uppercase tracking-wide text-[var(--ns-muted)]">About</p>
            <h1 class="mt-3 text-3xl font-semibold">About This Person</h1>
            <p class="mx-auto mt-4 max-w-xl text-lg text-[var(--ns-muted)]">
                The Biography and Certifications are being prepared. Check back soon.
            </p>
        </section>
    @endif
</x-public-layout>
