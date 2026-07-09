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

    public function test_preserves_unordered_and_ordered_lists(): void
    {
        // Arrange
        $input = "- one\n- two\n\n1. first\n2. second";

        // Act
        $output = safe_markdown($input);

        // Assert
        $this->assertStringContainsString('<ul>', $output);
        $this->assertStringContainsString('<ol>', $output);
        $this->assertStringContainsString('<li>one</li>', $output);
        $this->assertStringContainsString('<li>first</li>', $output);
    }

    public function test_preserves_code_blocks(): void
    {
        // Arrange
        $input = "Inline `code` and:\n\n```\nblock code\n```";

        // Act
        $output = safe_markdown($input);

        // Assert
        $this->assertStringContainsString('<code>code</code>', $output);
        $this->assertStringContainsString('<pre>', $output);
        $this->assertStringContainsString('block code', $output);
    }

    public function test_preserves_tables(): void
    {
        // Arrange — GitHub-flavored table (CommonMark "table" extension)
        $input = "| A | B |\n| - | - |\n| 1 | 2 |";

        // Act
        $output = safe_markdown($input);

        // Assert
        $this->assertStringContainsString('<table>', $output);
        $this->assertStringContainsString('<th>A</th>', $output);
        $this->assertStringContainsString('<td>1</td>', $output);
    }

    public function test_preserves_images_with_safe_sources(): void
    {
        // Arrange
        $input = '![alt text](https://example.com/pic.png)';

        // Act
        $output = safe_markdown($input);

        // Assert
        $this->assertStringContainsString('<img', $output);
        $this->assertStringContainsString('src="https://example.com/pic.png"', $output);
        $this->assertStringContainsString('alt="alt text"', $output);
    }
}
