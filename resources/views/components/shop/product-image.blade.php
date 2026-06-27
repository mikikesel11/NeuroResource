@props(['product'])

@if ($product->imageUrl)
    <img src="{{ $product->imageUrl }}"
         alt="{{ $product->imageAlt ?? $product->title }}"
         {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    {{-- Calm placeholder so the catalog works without product images (offline /
         fixtures). Decorative — the title is announced by the heading. --}}
    <div aria-hidden="true"
         {{ $attributes->merge(['class' => 'flex items-center justify-center bg-[var(--ns-bg)] text-3xl font-semibold text-[var(--ns-muted)]']) }}>
        {{ \Illuminate\Support\Str::of($product->title)->substr(0, 1)->upper() }}
    </div>
@endif
