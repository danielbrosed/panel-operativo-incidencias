<?php

declare(strict_types=1);

namespace App\Support;

use Inertia\Inertia;

/** The notice the layout shows after a redirect (flash prop `toast`). */
final class Toast
{
    public static function success(string $message): void
    {
        self::flash('success', $message);
    }

    public static function error(string $message): void
    {
        self::flash('error', $message);
    }

    private static function flash(string $type, string $message): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
