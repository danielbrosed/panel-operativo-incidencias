<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\UntrustedText;
use PHPUnit\Framework\TestCase;

class UntrustedTextTest extends TestCase
{
    public function test_control_and_invisible_characters_are_forbidden(): void
    {
        foreach (["a\e[2J", "a\u{202E}b", "a\u{200B}b", "a\u{2060}b", "a\u{00AD}b", "a\u{E0072}", "a\u{3164}b", "a\u{E0101}"] as $text) {
            $this->assertTrue(UntrustedText::containsForbidden($text), bin2hex($text));
        }
    }

    public function test_line_breaks_and_tabs_are_only_allowed_in_multiline_text(): void
    {
        $this->assertTrue(UntrustedText::containsForbidden("a\nb"));
        $this->assertFalse(UntrustedText::containsForbidden("a\nb\tc\r\n", multiline: true));
    }

    public function test_emoji_sequences_and_zwnj_are_text(): void
    {
        $this->assertFalse(UntrustedText::containsForbidden("Fuga \u{1F468}\u{200D}\u{1F527} \u{2764}\u{FE0F} a\u{200C}b"));
    }

    public function test_invalid_utf8_is_forbidden(): void
    {
        $this->assertTrue(UntrustedText::containsForbidden("a\xFF"));
    }

    public function test_for_terminal_leaves_one_line_without_controls_invisibles_or_console_tags(): void
    {
        $this->assertSame('[2J‹error›x‹/error›ab?', UntrustedText::forTerminal("\e[2J<error>x</error>\u{202E}a\u{E0072}\nb\xFF"));
    }
}
