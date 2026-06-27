@props(['post'])

<p {{ $attributes->merge(['class' => 'text-sm text-[var(--ns-muted)]']) }}>
    @if ($post->published_at)
        <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('F j, Y') }}</time>
    @endif
    @if ($post->reading_minutes)
        <span aria-hidden="true"> · </span>{{ $post->reading_minutes }} min read
    @endif
    @if ($post->author)
        <span aria-hidden="true"> · </span>by {{ $post->author->name }}
    @endif
</p>
