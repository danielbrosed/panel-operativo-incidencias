<?php

declare(strict_types=1);

namespace App\Support;

final class Like
{
    /**
     * Pattern for "contains", with the user's text matched literally: % and _ are LIKE wildcards and \ is
     * MySQL's default escape character, so a search for "100%" must not match "1000".
     */
    public static function contains(string $term): string
    {
        return '%'.addcslashes($term, '\\%_').'%';
    }
}
