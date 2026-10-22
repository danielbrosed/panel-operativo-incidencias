<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Text that comes from a person or from the database. Control and invisible characters can spoof how a text looks,
 * hide instructions in it and reach the terminal of whoever runs the console commands.
 */
final class UntrustedText
{
    /**
     * Regex alternative: every format character (\p{Cf}: bidirectional overrides and isolates, zero-width space,
     * word joiner, soft hyphen, BOM, Unicode tags…) except ZWNJ and ZWJ, which writing systems and emoji sequences
     * need, plus the line and paragraph separators, the Hangul fillers and the supplementary variation selectors.
     */
    private const INVISIBLE = '(?![\x{200C}\x{200D}])\p{Cf}|[\x{2028}\x{2029}\x{115F}\x{1160}\x{3164}\x{FFA0}\x{E0100}-\x{E01EF}]';

    /** Invalid UTF-8, a control character or an invisible one. Tabs and line breaks pass when $multiline. */
    public static function containsForbidden(string $text, bool $multiline = false): bool
    {
        $controls = $multiline ? '[^\P{Cc}\t\n\r]' : '\p{Cc}';

        return ! mb_check_encoding($text, 'UTF-8')
            || preg_match('/'.$controls.'|'.self::INVISIBLE.'/u', $text) === 1;
    }

    /**
     * The text on one terminal line, without control or invisible characters and without console tags. Symfony's
     * Table re-parses a cell when it wraps it, undoing OutputFormatter::escape(), so '<' and '>' become look-alikes.
     */
    public static function forTerminal(string $text): string
    {
        $clean = preg_replace('/\p{Cc}|'.self::INVISIBLE.'/u', '', mb_scrub($text, 'UTF-8')) ?? '';

        return strtr($clean, ['<' => '‹', '>' => '›']);
    }
}
