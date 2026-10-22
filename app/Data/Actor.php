<?php

declare(strict_types=1);

namespace App\Data;

/** Who performs an action. Its name is stored as a snapshot in logs and comments. */
final readonly class Actor
{
    public function __construct(public string $name) {}

    /** The diagnostic command, when it writes a fix. */
    public static function system(): self
    {
        return new self(config()->string('incidents.diagnostics.actor'));
    }
}
