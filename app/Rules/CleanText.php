<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\UntrustedText;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Text a person typed, with nothing a browser would not send: invalid UTF-8 would make MySQL reject the insert (a
 * 500), and control or invisible characters are refused as UntrustedText defines them.
 */
final class CleanText implements ValidationRule
{
    /** @param  bool  $multiline  tabs and line breaks allowed (description, comment, reason) */
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

        if (UntrustedText::containsForbidden($value, $this->multiline)) {
            $fail('validation.control_characters')->translate();
        }
    }
}
