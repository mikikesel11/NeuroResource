<?php

declare(strict_types=1);

use HtmlSanitizer\Sanitizer;
use Illuminate\Support\Str;

/**
 * Convert Markdown to sanitized HTML safe for {!! !!} output.
 *
 * Two-pass approach:
 *  1. CommonMark strips raw HTML (html_input: strip) and blocks unsafe links.
 *  2. tgalopin/html-sanitizer removes any remaining XSS-capable constructs via
 *     a strict tag-and-attribute whitelist.
 */
if (! function_exists('safe_markdown')) {
    function safe_markdown(string $content): string
    {
        $html = Str::markdown($content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $sanitizer = Sanitizer::create([
            'extensions' => ['basic', 'code', 'image', 'list', 'table', 'extra'],
        ]);

        return $sanitizer->sanitize($html);
    }
}
