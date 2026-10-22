<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Browsers always send UTF-8, but a crafted request may not. Without this, MySQL rejects the bytes on insert
 * (error 1366) and the person gets a 500 instead of a validation message.
 */
final class ValidUtf8 implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
            $fail('validation.utf8')->translate();
        }
    }
}
