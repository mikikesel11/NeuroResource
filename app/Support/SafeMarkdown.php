<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Convert Markdown to sanitized HTML safe for {!! !!} output.
 *
 * Two-pass approach:
 *  1. CommonMark strips raw HTML (html_input: strip) and blocks unsafe links.
 *  2. symfony/html-sanitizer removes any remaining XSS-capable constructs via
 *     a strict tag-and-attribute whitelist (W3C-defined safe element set).
 */
if (! function_exists('safe_markdown')) {
    function safe_markdown(string $content): string
    {
        $html = Str::markdown($content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowMediaSchemes(['https', 'http']);

        return (new HtmlSanitizer($config))->sanitize($html);
    }
}
