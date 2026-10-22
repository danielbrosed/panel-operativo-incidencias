<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Text a person typed, with nothing a browser would not send.
 *
 * - Invalid UTF-8: MySQL would reject it on insert (error 1366) and the person would get a 500.
 * - Control characters (NUL, ESC, BEL…) and bidirectional overrides: they can spoof what a text looks like and
 *   reach the terminal of whoever runs `incidents:diagnose`. Tabs and line breaks are allowed only in multi-line
 *   fields (description, comment, reason).
 */
final class CleanText implements ValidationRule
{
    /**
     * Regex character-class body: bidirectional overrides and isolates, plus invisible format characters (Arabic
     * letter mark, zero-width space, LRM/RLM, line and paragraph separators, BOM). U+200D (zero-width joiner) is
     * left out on purpose: emoji sequences use it. Shared with everything that prints untrusted text.
     */
    public const FORMAT_CHARACTERS = '\x{202A}-\x{202E}\x{2066}-\x{2069}\x{061C}\x{200B}\x{200E}\x{200F}\x{2028}\x{2029}\x{FEFF}';

    public function __construct(private readonly bool $multiline = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $fail('validation.utf8')->translate();

            return;
        }

        $controls = $this->multiline ? '[^\P{Cc}\t\n\r]' : '\p{Cc}';

        if (preg_match('/'.$controls.'|['.self::FORMAT_CHARACTERS.']/u', $value) === 1) {
            $fail('validation.control_characters')->translate();
        }
    }
}
