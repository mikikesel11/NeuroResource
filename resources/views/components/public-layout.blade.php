@props(['title' => null])

@php
    // Public nav. Items map to named routes; built incrementally — unbuilt ones
    // point at the shared "coming soon" page so nothing 404s. See design §2.
    $nav = [
        ['route' => 'home',      'label' => __('messages.nav.home')],
        ['route' => 'shop',      'label' => __('messages.nav.shop')],
        ['route' => 'blog',      'label' => __('messages.nav.blog')],
        ['route' => 'resources', 'label' => __('messages.nav.resources')],
        ['route' => 'about',     'label' => __('messages.nav.about')],
        ['route' => 'play',      'label' => __('messages.nav.play')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    {{-- Apply saved theme/motion/text prefs before first paint (no flash). --}}
    <script>
        (function () {
            try {
                var p = JSON.parse(localStorage.getItem('ns:preferences') || '{}');
                var root = document.documentElement;
                root.setAttribute('data-theme',
                    p.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
                var rm = (p.reduceMotion != null) ? p.reduceMotion
                    : matchMedia('(prefers-reduced-motion: reduce)').matches;
                root.setAttribute('data-reduce-motion', rm ? 'true' : 'false');
                if (p.textScale)     root.style.setProperty('--ns-text-scale', p.textScale);
                if (p.lineHeight)    root.style.setProperty('--ns-line-height', p.lineHeight);
                if (p.letterSpacing != null) root.style.setProperty('--ns-letter-spacing', p.letterSpacing + 'em');
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.analytics')
</head>
<body class="min-h-screen flex flex-col bg-[var(--ns-bg)] text-[var(--ns-text)]">
    <a href="#main-content" class="ns-skip-link">{{ __('messages.a11y.skip_to_content') }}</a>

    <header class="border-b border-[var(--ns-border)]">
        <div class="mx-auto max-w-5xl px-4 py-3">
            {{-- Top row: brand left, settings always pinned right (so the
                 dropdown's right-0 anchor never overflows the viewport). --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('home') }}" class="text-xl font-semibold tracking-tight" wire:navigate>
                    Neuro<span class="text-[var(--ns-accent)]">Scouts</span>
                </a>
                <div class="ml-auto">
                    <x-a11y-preferences />
                </div>
            </div>

            <nav aria-label="Primary" class="mt-3">
                <ul class="flex flex-wrap gap-x-5 gap-y-2 text-[var(--ns-muted)]">
                    @foreach ($nav as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <li>
                            <a href="{{ route($item['route']) }}" wire:navigate
                               @if($active) aria-current="page" @endif
                               class="hover:text-[var(--ns-text)] focus-visible:text-[var(--ns-text)] {{ $active ? 'text-[var(--ns-text)] font-medium underline underline-offset-4' : '' }}">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </header>

    <main id="main-content" tabindex="-1" class="flex-1 focus:outline-none">
        {{ $slot }}
    </main>

    <footer class="border-t border-[var(--ns-border)] mt-16">
        <div class="mx-auto max-w-5xl px-4 py-8 text-sm text-[var(--ns-muted)] flex flex-wrap justify-between gap-4">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Built for NeuroDivergent people.</p>
            <p>Made to be Calm, Clear, and Accessible.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
