<?php

declare(strict_types=1);

namespace App\Console\Commands\Concerns;

use App\Rules\CleanText;

trait PrintsUntrustedText
{
    /**
     * Text read from the database (titles, names, reasons), made safe to print on one terminal line: without
     * control characters (ESC, BEL, line breaks…) or bidirectional overrides, and without console tags. Symfony's
     * Table re-parses a cell when it wraps it, undoing OutputFormatter::escape(), so '<' and '>' are swapped for
     * look-alikes instead of escaped. Display only: the stored value is not touched.
     */
    private static function untrusted(string $text): string
    {
        $clean = preg_replace('/[\p{Cc}'.CleanText::FORMAT_CHARACTERS.']/u', '', mb_scrub($text, 'UTF-8')) ?? '';

        return strtr($clean, ['<' => '‹', '>' => '›']);
    }
}
