<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

class SafeMarkdownTest extends TestCase
{
    public function test_renders_basic_markdown_to_html(): void
    {
        // Arrange
        $input = '**Bold** and _italic_ text.';

        // Act
        $output = safe_markdown($input);

        // Assert
        $this->assertStringContainsString('<strong>Bold</strong>', $output);
        $this->assertStringContainsString('<em>italic</em>', $output);
    }

    public function test_strips_script_tags(): void
    {
        // Arrange
        $input = 'Hello <script>alert("xss")</script> world';

        // Act
        $output = safe_markdown($input);

        // Assert — the executable tag must be gone; bare text is harmless
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('</script>', $output);
    }

    public function test_strips_inline_event_handlers(): void
    {
        // Arrange — inject an onerror attribute inside an img tag via raw HTML
        $input = 'Before <img src="x" onerror="alert(1)"> After';

        // Act
        $output = safe_markdown($input);

        // Assert
        $this->assertStringNotContainsString('onerror', $output);
        $this->assertStringNotContainsString('alert(1)', $output);
    }

    public function test_removes_javascript_hrefs(): void
    {
        // Arrange
        $input = '[click me](javascript:alert(document.cookie))';

        // Act
        $output = safe_markdown($input);

        // Assert
        $this->assertStringNotContainsString('javascript:', $output);
        $this->assertStringNotContainsString('alert(document.cookie)', $output);
    }

    public function test_preserves_safe_links(): void
    {
        // Arrange
        $input = '[Learn more](https://example.com)';

        // Act
        $output = safe_markdown($input);

        // Assert
        $this->assertStringContainsString('href="https://example.com"', $output);
        $this->assertStringContainsString('Learn more', $output);
    }
}
